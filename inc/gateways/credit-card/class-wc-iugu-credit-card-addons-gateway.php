<?php

if (!defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * iugu Payment Credit Card Addons Gateway class.
 *
 * Integration with WooCommerce Subscriptions and Pre-orders.
 *
 * @class   WC_Iugu_Credit_Card_Addons_Gateway
 * @extends WC_Iugu_Credit_Card_Gateway
 */
class WC_Iugu_Credit_Card_Addons_Gateway extends WC_Iugu_Credit_Card_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {

		parent::__construct();

		if (class_exists('WC_Subscriptions_Order')) {

			add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);

			add_action('woocommerce_subscription_failing_payment_method_updated_' . $this->id, array($this, 'update_failing_payment_method'), 10, 2);

			add_action('wcs_resubscribe_order_created', array($this, 'delete_resubscribe_meta'), 10);

			/**
			 * Allow store managers to manually set Simplify as the payment method on a subscription.
			 */
			add_filter('woocommerce_subscription_payment_meta', array($this, 'add_subscription_payment_meta'), 10, 2);

			add_filter('woocommerce_subscription_validate_payment_meta', array($this, 'validate_subscription_payment_meta'), 10, 2);

      $maybe_iugu_handle_subscriptions = get_option('enable_iugu_handle_subscriptions');

      if ($maybe_iugu_handle_subscriptions === 'yes') {

        /**
         * Process subscription on-hold.
         */
        add_action('woocommerce_subscription_on-hold_' . $this->id, array($this, 'iugu_subscription_on_hold'), 10);

        /**
         * Process subscription active.
         */
        add_action('woocommerce_subscription_activated_' . $this->id, array($this, 'iugu_subscription_activate'), 10);

        /**
         * Process subscription cancelled.
         */
        add_action('woocommerce_subscription_cancelled_' . $this->id, array($this, 'iugu_subscription_cancelled'), 10);

      } // end if;

		} // end if;

		if (class_exists('WC_Pre_Orders_Order')) {

			add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->id, array($this, 'process_pre_order_release_payment'));

		} // end if;

		add_action('woocommerce_api_wc_iugu_credit_card_addons_gateway', array($this->api, 'notification_handler'));

	} // end __constructor;

	/**
	 * Process the payment.
	 *
	 * @param  int $order_id WooCommerce order ID.
	 * @return array
	 */
	public function process_payment($order_id) {

		/**
		 * Processing subscription.
		 */
		if ($this->api->order_contains_subscription($order_id)) {

      $maybe_iugu_handle_subscriptions = get_option('enable_iugu_handle_subscriptions');

      if ($maybe_iugu_handle_subscriptions === 'yes') {

				return $this->process_iugu_subscription($order_id);

			} else {

				return $this->process_subscription($order_id);

			} // end if;


		} elseif ($this->api->order_contains_pre_order($order_id)) {

			/**
			 * Processing pre-order.
			 */
			return $this->process_pre_order($order_id);

		} else {

			/**
			 * Processing regular product.
			 */
			return parent::process_payment($order_id);

		} // end if;

	} // end process_payment;

	/**
	 * Process the subscription.
	 *
	 * @param WC_Order $order
	 * @return array
	 */
	protected function process_subscription($order_id) {

		try {

			$order = new WC_Order($order_id);

			/**
			 * Tratamento do salvamento do cartão.
			 */
			if (isset( $_POST['iugu_token']) && isset($_POST['iugu_save_card']) && $_POST['iugu_save_card'] == 'on') {

				/**
				 * Temos token, e o usuário quer salvar o cartão. Então vamos salvar e colocar seu ID em $_POST,
				 * e remover o token, pois ele não pode ser reutilizado.
				 */
				$_POST['customer_payment_method_id'] = $this->api->create_customer_payment_method($order, $_POST['iugu_token']);

				unset($_POST['iugu_token']);

			} // end if;

			/**
			 * Processamento do pagamento.
			 */
			if (!isset($_POST['customer_payment_method_id'])) {
				/**
				 * Não temos nem iugu_token e nem customer_payment_method_id, não há como concluir o pagamento.
				 */
				if ('yes' === $this->debug) {

					$this->log->add($this->id, 'Error doing the charge for order ' . $order->get_order_number() . ': Missing the "iugu_token" and "customer_payment_method_id".');

				} // end if;

				$this->api->add_error('<strong>' . esc_attr( $this->title ) . '</strong>: ' . __( 'Please, make sure your credit card details have been entered correctly and that your browser supports JavaScript.', 'iugu-woocommerce'));

				return array(
					'result'   => 'fail',
					'redirect' => ''
				);

			} // end if;

			$this->save_subscription_meta($order->get_id(), $_POST['customer_payment_method_id']);

			$payment_response = $this->process_subscription_payment($order, $order->get_total());

			if (isset($payment_response) && is_wp_error($payment_response)) {
				/**
				 * Se a chamada foi mal sucedida, e apenas se o usuário tentou salvar esta forma de pagamento, ela será excluída.
				 */
				if(isset( $_POST['iugu_save_card']) && $_POST['iugu_save_card'] == 'on') {

					$this->api->remove_payment_method($order, $_POST['customer_payment_method_id']);

				} // end if;

				throw new Exception( $payment_response->get_error_message() );

			} else {
				/**
				 * Se a chamada foi bem sucedida, a forma de pagamento torna-se a default.
				 */
				$this->api->set_default_payment_method($order, $_POST['customer_payment_method_id']);
				/**
				 * Remove cart
				 */
				$this->api->empty_card();

				/**
				 * Return thank you page redirect
				 */
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order )
				);

			} // end if;

		} catch (Exception $e) {

			$this->api->add_error('<strong>' . esc_attr($this->title) . '</strong>: ' . $e->getMessage());

			return array(
				'result'   => 'fail',
				'redirect' => ''
			);

		} // end try;

	} // end process_subscription;

	/**
	 * Process a subscription using Iugu Subscriptions.
	 *
	 * @since 2.20
	 *
	 * @param string $order_id WooCommerce Order ID.
	 * @return array with the result and possible error messages
	 */
	protected function process_iugu_subscription($order_id) {

		try {

			$order 	= new WC_Order( $order_id );

			$customer_id = $this->api->get_customer_id($order);

			if (!$customer_id) {

				throw new Exception(__('Customer not found.', 'iugu-woocommerce'));

			} // end if;

			$plan_id	= $this->api->get_product_plan_id($order_id);

			if (!$plan_id) {

				throw new Exception(__('Plan not found.', 'iugu-woocommerce'));

			} // end if;

			$plan	= $this->api->get_iugu_plan($plan_id);

			/**
			 * Get the payment method
			 */
			if (isset($_POST['iugu_token'])) {

				$iugu_payment_method = $this->api->create_customer_payment_method($order, $_POST['iugu_token']);

				if (!isset($iugu_payment_method['id'])) {

					throw new Exception($iugu_payment_method['errors']);

				} // end if;

				$payment_method_id = $iugu_payment_method['id'];

			}	else if (isset($_POST['customer_payment_method_id'])) {

				$token = new WC_Payment_Token_CC($_POST['customer_payment_method_id']);

				$payment_method_id = $token->get_token();

			} // end if;

			$this->api->set_default_payment_method($order, $payment_method_id);

			$create_subscription = $this->api->create_iugu_subscription($order, $plan, $customer_id);

			if (isset($create_subscription['recent_invoices'])) {

        $wcs_subscriptions = wcs_get_subscriptions_for_order($order_id);

        foreach ($wcs_subscriptions as $wcs_subscription_key => $wcs_subscription_value) {

          /**
           * Save iugu subscription data in the WooCommerce Subscriptions subscription;
           */
          update_post_meta($wcs_subscription_key, '_wcs_iugu_subscription_id', $create_subscription['id']);

        } // end foreach;

        $invoice_data = $this->api->get_invoice_by_id($create_subscription['recent_invoices'][0]['id']);

        /**
         * Save first invoice data.
         */
				update_post_meta($order->get_id(), '_iugu_wc_transaction_data', $invoice_data['pix']);

        update_post_meta($order->get_id(), '_transaction_id', $invoice_data['id']);

				$payment_response = $this->process_iugu_subscription_payment($order, $create_subscription, $order->get_total());

				if (isset($_POST['iugu_token'])) {

					$set_wc_payment_method = $this->api->set_wc_payment_method($iugu_payment_method);

				} // end if;

				if ($payment_response) {

					/**
					 * Return thank you page redirect
					 */
					return array(
						'result'   => 'success',
						'redirect' => $this->get_return_url($order)
					);

				} // end if

			} else {

				throw new Exception($create_subscription['errors']);

			} // end if;

		} catch (Exception $e) {

			$this->api->add_error('<strong>' . esc_attr( $this->title ) . '</strong>: ' . $e->getMessage());

			return array(
				'result'   => 'fail',
				'redirect' => ''
			);

		} // end try;

	} // end process_iugu_subscription;

	/**
	 * Process Iugu Subscription payment.
	 *
	 * @since 2.20
	 *
	 * @param WC_order $order WooCommerce Order
	 * @param int      $amount Subscription Amount
	 * @return bool|WP_Error
	 */
	public function process_iugu_subscription_payment($order, $subscription, $amount = 0) {

		if ( 0 == $amount ) {

			/**
			 * Payment complete.
			 */
			$order->payment_complete();

			return true;

		} // end if;

		/**
		 * Update the iugu subscription information in the user's meta.
		 */
		foreach(wcs_get_subscriptions_for_order($order->get_id()) as $wcs_subscription) {

			update_post_meta($wcs_subscription->get_id(), '_iugu_subscription_id', $subscription['id']);

		} // end foreach;

		if ($subscription['recent_invoices'][0]['status'] === 'paid') {

			$order->add_order_note(__('iugu: Subscription paid successfully by credit card.', 'iugu-woocommerce'));

			$order->payment_complete();

			return true;

		} else {

			return new WP_Error( 'iugu_subscription_error', __( 'iugu: Subscription payment failed. Credit card declined.', 'iugu-woocommerce'));

		} // end if;

	} // end process_iugu_subscription_payment;

  /**
	 * Set the status of a iugu subscription to 'suspended'
	 *
	 * @since 2.20
	 *
	 * @param object $wcs_subscription WooCommerce Subscription object.
	 * @return void.
	 */
	public function iugu_subscription_on_hold($wcs_subscription) {

		$iugu_subscription_id = get_post_meta($wcs_subscription->get_id(), '_wcs_iugu_subscription_id', true);

		if ($iugu_subscription_id) {

			$this->api->suspend_iugu_subscription($iugu_subscription_id);

		} // end if;

	} // end iugu_subscription_on_hold;

  /**
	 * Activates a iugu subscription.
	 *
	 * @since 2.20
	 *
	 * @param object $wcs_subscription WooCommerce Subscription object.
	 * @return void.
	 */
	public function iugu_subscription_activate($wcs_subscription) {

		$iugu_subscription_id = get_post_meta($wcs_subscription->get_id(), '_wcs_iugu_subscription_id', true);

		if ($iugu_subscription_id){

			$this->api->unsuspend_iugu_subscription($iugu_subscription_id);

		} // end if;

	} // end iugu_subscription_activate;

/**
	 * Deletes a iugu subscription
	 *
	 * @since 2.20
	 *
	 * @param object $wcs_subscription WooCommerce Subscription object.
	 * @return void.
	 */
	public function iugu_subscription_cancelled($wcs_subscription) {

		$iugu_subscription_id = get_post_meta($wcs_subscription->get_id(), '_wcs_iugu_subscription_id');

		if ($iugu_subscription_id) {

			$this->api->delete_iugu_subscription($iugu_subscription_id[0]);

		} // end if;

	} // end iugu_subscription_cancelled;

	/**
	 * Process the pre-order.
	 *
	 * @param WC_Order $order
	 * @return array
	 */
	protected function process_pre_order($order_id) {
		if ( WC_Pre_Orders_Order::order_requires_payment_tokenization( $order_id ) ) {
			try {
				$order = new WC_Order( $order_id );

				if ( ! isset( $_POST['iugu_token'] ) ) {
					if ( 'yes' == $this->debug ) {
						$this->log->add( $this->id, 'Error doing the pre-order for order ' . $order->get_order_number() . ': Missing the "iugu_token".' );
					}

					$error_msg = __( 'Please, make sure your credit card details have been entered correctly and that your browser supports JavaScript.', 'iugu-woocommerce' );

					throw new Exception( $error_msg );
				}

				// Create customer payment method.
				$payment_method_id = $this->api->create_customer_payment_method( $order, $_POST['iugu_token'] );
				if ( ! $payment_method_id ) {
					if ( 'yes' == $this->debug ) {
						$this->log->add( $this->id, 'Invalid customer method ID for order ' . $order->get_order_number() );
					}

					$error_msg = __( 'An error occurred while trying to save your data. Please, contact us to get help.', 'iugu-woocommerce' );

					throw new Exception( $error_msg );
				}

				// Save the payment method ID in order data.
				update_post_meta( $order->get_id(), '_iugu_customer_payment_method_id', $payment_method_id );

				// Reduce stock levels
				$order->reduce_order_stock();

				// Remove cart
				$this->api->empty_card();

				// Is pre ordered!
				WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );

				// Return thank you page redirect
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order )
				);

			} catch ( Exception $e ) {
				$this->api->add_error( '<strong>' . esc_attr( $this->title ) . '</strong>: ' . $e->getMessage() );

				return array(
					'result'   => 'fail',
					'redirect' => ''
				);
			}

		} else {
			return parent::process_payment( $order_id );
		}
	} // end process_pre_order.

	/**
	 * Store the iugu customer payment method id on the order and subscriptions in the order.
	 *
	 * @param int $order_id
	 * @param string $payment_method_id
	 */
	protected function save_subscription_meta($order_id, $payment_method_id) {

		$payment_method_id = wc_clean($payment_method_id);

		update_post_meta($order_id, '_iugu_customer_payment_method_id', $payment_method_id);

		/**
		 * Also store it on the subscriptions being purchased in the order.
		 */
		foreach(wcs_get_subscriptions_for_order($order_id) as $subscription) {

			update_post_meta($subscription->get_id(), '_iugu_customer_payment_method_id', $payment_method_id);

		} // end foreach;

	} // end save_subscription_meta;

	/**
	 * Process subscription payment.
	 *
	 * @param WC_order $order WooCommerce Order
	 * @param int      $amount Subscription Amount
	 * @return bool|WP_Error
	 */
	public function process_subscription_payment($order = '', $amount = 0) {

		if ( 0 == $amount ) {

			/**
			 * Payment complete.
			 */
			$order->payment_complete();

			return true;

		} // end if;

		if ('yes' == $this->debug) {

			$this->log->add($this->id, 'Processing a subscription payment for order ' . $order->get_order_number());

		} // end if;

		$payment_method_id = get_post_meta($order->get_id(), '_iugu_customer_payment_method_id', true);

		if (!$payment_method_id) {

			if ( 'yes' == $this->debug ) {

				$this->log->add($this->id, 'Missing customer payment method ID in subscription payment for order ' . $order->get_order_number());

			} // end if;

			return new WP_Error('iugu_subscription_error', __('Customer payment method not found!', 'iugu-woocommerce'));

		} // end if;

		$charge = $this->api->create_charge($order, array( 'customer_payment_method_id' => $payment_method_id));

		if (isset($charge['errors']) && ! empty($charge['errors'])) {

			$error = is_array($charge['errors']) ? current($charge['errors']) : $charge['errors'];

			return new WP_Error('iugu_subscription_error', $error);

		} // end if;

		update_post_meta($order->get_id(), '_transaction_id', sanitize_text_field( $charge['invoice_id']));

		/**
		 * Save only in old versions.
		 */
		if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.1.12', '<=' )) {

			update_post_meta($order->get_id(), __('iugu transaction details', 'iugu-woocommerce'), 'https://iugu.com/a/invoices/' . sanitize_text_field($charge['invoice_id']));

		} // end if;

		if ( true == $charge['success'] ) {

			$order->add_order_note(__('iugu: Subscription paid successfully by credit card.', 'iugu-woocommerce'));

			$order->payment_complete();

			return true;

		} else {

			return new WP_Error('iugu_subscription_error', __( 'iugu: Subscription payment failed. Credit card declined.', 'iugu-woocommerce'));

		} // end if;

	} // end process_subscription_payment;

  /**
   * Scheduled subscription payment.
   *
   * @param float $amount_to_charge The amount to charge.
   * @param WC_Order $renewal_order A WC_Order object created to record the renewal payment.
   */
  public function process_scheduled_subscription_payment($amount_to_charge, $renewal_order) {

    $maybe_iugu_handle_subscriptions = get_option('enable_iugu_handle_subscriptions');

    if ($maybe_iugu_handle_subscriptions === 'yes') {

      $result = $this->process_iugu_subscription_payment($renewal_order, $amount_to_charge);

    } else {

      $result = $this->process_subscription_payment($renewal_order, $amount_to_charge);

      if (is_wp_error($result)) {

        $renewal_order->update_status('failed', $result->get_error_message());

      } // end if;

    }

  } // end scheduled_subscription_payment;

	/**
	 * Update the customer_id for a subscription
	 *
	 * @param WC_Subscription $subscription The subscription for which the failing payment method relates.
	 * @param WC_Order $renewal_order The order which recorded the successful payment (to make up for the failed automatic payment).
	 * @return void.
	 */
	public function update_failing_payment_method($subscription, $renewal_order) {

		update_post_meta($subscription->get_id(), '_iugu_customer_payment_method_id', get_post_meta($renewal_order->id, '_iugu_customer_payment_method_id', true));

	} // end update_failing_payment_method;

	/**
	 * Include the payment meta data required to process automatic recurring payments so that store managers can.
	 * manually set up automatic recurring payments for a customer via the Edit Subscription screen in Subscriptions v2.0+.
	 *
	 * @param array $payment_meta associative array of meta data required for automatic payments
	 * @param WC_Subscription $subscription An instance of a subscription object
	 * @return array
	 */
	public function add_subscription_payment_meta($payment_meta, $subscription) {

		$payment_meta[ $this->id ] = array(
			'post_meta' => array(
				'_iugu_customer_payment_method_id' => array(
					'value' => get_post_meta($subscription->get_id(), '_iugu_customer_payment_method_id', true),
					'label' => 'iugu Payment Method ID',
				),
			),
		);

		return $payment_meta;

	} // end add_subscription_payment_meta;

	/**
	 * Validate the payment meta data required to process automatic recurring payments so that store managers can.
	 *
	 * @param  string $payment_method_id The ID of the payment method to validate.
	 * @param  array $payment_meta associative array of meta data required for automatic payments.
	 * @return array
	 * @throws Exception
	 */
	public function validate_subscription_payment_meta( $payment_method_id, $payment_meta ) {

		if ($this->id === $payment_method_id) {

			if (!isset( $payment_meta['post_meta']['_iugu_customer_payment_method_id']['value']) || empty($payment_meta['post_meta']['_iugu_customer_payment_method_id']['value'])) {

				throw new Exception('A "_iugu_customer_payment_method_id" value is required.');

			} // end if;

		} // end if;

	} // end validate_subscription_payment_meta;

	/**
	 * Don't transfer customer meta to resubscribe orders.
	 *
	 * @param object $resubscribe_order The order created for the customer to resubscribe to the old expired/cancelled subscription.
	 * @return void.
	 */
	public function delete_resubscribe_meta($resubscribe_order) {

		delete_post_meta($resubscribe_order->id, '_iugu_customer_payment_method_id');

	} // end delete_resubscribe_meta;

	/**
	 * Process a pre-order payment when the pre-order is released.
	 *
	 * @param WC_Order $order
	 * @return void.
	 */
	public function process_pre_order_release_payment($order) {

		if ('yes' == $this->debug) {

			$this->log->add($this->id, 'Processing a pre-order release payment for order ' . $order->get_order_number());

		} // end if;

		try {

			$payment_method_id = get_post_meta($order->get_id(), '_iugu_customer_payment_method_id', true);

			if (!$payment_method_id) {

				if ('yes' == $this->debug) {

					$this->log->add($this->id, 'Missing customer payment method ID in subscription payment for order ' . $order->get_order_number());

				} // end if;

				return new Exception(__('Customer payment method not found!', 'iugu-woocommerce'));

			} // end if;

			$charge = $this->api->create_charge($order, array( 'customer_payment_method_id' => $payment_method_id));

			if ( isset($charge['errors']) && ! empty($charge['errors'])) {

				$error = is_array($charge['errors']) ? current($charge['errors']) : $charge['errors'];

				return new Exception($error);

			} // end if;

			update_post_meta($order->get_id(), '_transaction_id', sanitize_text_field($charge['invoice_id']));

			/**
			 * Save only in old versions.
			 */
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1.12', '<=' ) ) {

				update_post_meta($order->get_id(), __('iugu transaction details', 'iugu-woocommerce'), 'https://iugu.com/a/invoices/' . sanitize_text_field($charge['invoice_id']));

			} // end if;

			if (!$charge['success']) {

				return new Exception(__('iugu: Credit card declined.', 'iugu-woocommerce'));

			} // end if;

			$order->add_order_note(__('iugu: Invoice paid successfully by credit card.', 'iugu-woocommerce'));

			$order->payment_complete();

		} catch (Exception $e) {

			$order_note = sprintf(__('iugu: Pre-order payment failed (%s).', 'iugu-woocommerce'), $e->getMessage());

			// Mark order as failed if not already set,
			// otherwise, make sure we add the order note so we can detect when someone fails to check out multiple times
			if ( 'failed' != $order->get_status() ) {

				$order->update_status( 'failed', $order_note );

			} else {

				$order->add_order_note( $order_note );

			} // end if;

		} // end try;

	} // end process_pre_order_release_payment;

	/**
	 * Handles API notifications.
	 *
	 * @return void
	 */
	public function notification_handler() {

		$this->api->notification_handler();

	} // end notification_handler;

	/**
	 * Gateway payment fields.
	 *
	 * @return void
	 */
	public function payment_fields() {

		parent::payment_fields();

	} // end payment_fields;

	/**
	 * Scheduled subscription payment.
	 *
	 * @since 2.20
	 *
	 * @param float $amount_to_charge The amount to charge.
	 * @param WC_Order $renewal_order A WC_Order object created to record the renewal payment.
	 * @return void.
	 */
	public function scheduled_iugu_subscription_payment( $amount_to_charge, $renewal_order ) {

		if (0 == $amount_to_charge) {

			/**
			 * Payment complete.
			 */
			$renewal_order->payment_complete();

			return true;

		} // end if;

		/**
		 * Get Iugu Subscription ID.
		 */
		$_iugu_subscription_id = get_post_meta($renewal_order->get_id(), '_iugu_subscription_id', true);

		if (!$_iugu_subscription_id) {

			//$this->scheduled_subscription_payment($amount_to_charge, $renewal_order);

			return true;

		} // end if;

		$subscription = $this->api->get_subscription($_iugu_subscription_id);

		if ($subscription['errors']) {

			$renewal_order->update_status('failed', $subscription['errors']);

		} // end if;

		update_post_meta($renewal_order->get_id(), '_transaction_id', sanitize_text_field($subscription['invoice']->id));

		if ($subscription['invoice']->status == 'paid') {

			$renewal_order->add_order_note( __('iugu: Subscription paid successfully by credit card.', 'iugu-woocommerce'));

			$renewal_order->payment_complete();

			return true;

		} else {

			$renewal_order->update_status('failed', $subscription->errors);

		} // end if;

	} // end scheduled_iugu_subscription_payment;

} // end WC_Iugu_Credit_Card_Addons_Gateway;
