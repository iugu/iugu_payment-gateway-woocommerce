<?php
if (!defined('ABSPATH')) {

  exit;

} // end if;

/**
 * iugu Payment Bank Slip Addons Gateway class.
 *
 * Integration with WooCommerce Subscriptions and Pre-orders.
 *
 * @class   WC_Iugu_Pix_Addons_Gateway
 * @extends WC_Iugu_Pix_Gateway
 * @version 1.0.0
 * @author  iugu
 */
class WC_Iugu_Pix_Addons_Gateway extends WC_Iugu_Pix_Gateway {

  /**
   * Constructor.
   */
  public function __construct() {

    parent::__construct();

    if (class_exists('WC_Subscriptions_Order')) {

      /**
       * For failed attempts.
       */
      add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'process_scheduled_subscription_payment' ), 10, 2 );

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

    add_action('woocommerce_api_wc_iugu_pix_addons_gateway', array( $this->api, 'notification_handler'));

  } // end if;

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

		} else {

			/**
			 * Processing regular product.
			 */
			return parent::process_payment($order_id);

		} // end if;

	} // end process_payment;

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

			$order 	= new WC_Order($order_id);

			$customer_id = $this->api->get_customer_id($order);

			/**
			 * Get the payment method
			 */
			if (isset($_POST['iugu_token'])) {

				$payment_method_id = $this->api->create_customer_payment_method($order, $_POST['iugu_token']);

			}	else if (isset($_POST['customer_payment_method_id'])) {

				$payment_method_id = $_POST['customer_payment_method_id'];

			} // end if;

      if (isset($payment_method_id)) {

        $this->api->set_default_payment_method($order, $payment_method_id);

      } // end if;

			$plan_id	= $this->api->get_product_plan_id($order_id);

			if ($plan_id) {

				$plan	= $this->api->get_iugu_plan($plan_id);

			} // end if;

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

				throw new Exception($create_subscription['error']);

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
   * @param array    $iugu_subscription Iugu subscription array.
	 * @param int      $amount Subscription Amount
	 * @return bool|WP_Error
	 */
	public function process_iugu_subscription_payment($order, $subscription, $amount = 0) {

		if (0 == $amount) {

			/**
			 * Payment complete.
			 */
			$order->payment_complete();

			return true;

		} // end if;

    /**
     * Get Iugu Subscription Status.
     */
    $iugu_subscription = $this->api->get_iugu_subscription($subscription['id']);

		if ($iugu_subscription['recent_invoices'][0]['status'] === 'paid') {

			$order->add_order_note(__('Subscription paid successfully by Iugu - PIX.', 'iugu-woocommerce'));

			$order->payment_complete();

		} else {

      $order->add_order_note(__('Iugu Subscription waiting payment.', 'iugu-woocommerce'));

		} // end if;

    return true;

	} // end process_iugu_subscription_payment;

  /**
   * Process the subscription.
   *
   * @param WC_Order $order
   *
   * @return array
   */
  protected function process_subscription($order_id) {

    try {

      $order = new WC_Order($order_id);

      $payment_response = $this->process_subscription_payment($order, $order->get_total() );

      if (isset($payment_response) && is_wp_error($payment_response)) {

        throw new Exception($payment_response->get_error_message());

      } else {

        // Return thank you page redirect
        return array(
          'result'   => 'success',
          'redirect' => $this->get_return_url( $order )
        );

      }

    } catch ( Exception $e ) {

      $this->api->add_error( '<strong>' . esc_attr( $this->title ) . '</strong>: ' . $e->getMessage() );

      return array(
        'result'   => 'fail',
        'redirect' => ''
      );

    }

  } // end process_subscription;

  /**
   * Process subscription payment.
   *
   * @param WC_order $order
   * @param int      $amount (default: 0)
   *
   * @return bool|WP_Error
   */
  public function process_subscription_payment( $order = '', $amount = 0 ) {

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

    $charge = $this->api->create_charge($order);

    if ( isset( $charge['errors'] ) && ! empty( $charge['errors'] ) ) {

      $error = is_array( $charge['errors'] ) ? current( $charge['errors'] ) : $charge['errors'];

      return new WP_Error( 'iugu_subscription_error', $error );

    }

    $payment_data = array_map(
      'sanitize_text_field',
      array(
        'pdf' => $charge['pdf']
      )
    );
    update_post_meta( $order->get_id(), '_iugu_wc_transaction_data', $payment_data );
    update_post_meta( $order->get_id(), __( 'iugu bank slip URL', 'iugu-woocommerce' ), $payment_data['pdf'] );
    update_post_meta( $order->get_id(), '_transaction_id', sanitize_text_field( $charge['invoice_id'] ) );

    // Save only in old versions.
    if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1.12', '<=' ) ) {
      update_post_meta( $order->get_id(), __( 'iugu transaction details', 'iugu-woocommerce' ), 'https://iugu.com/a/invoices/' . sanitize_text_field( $charge['invoice_id'] ) );
    }

    $order_note = __( 'iugu: The customer generated a bank slip. Awaiting payment confirmation.', 'iugu-woocommerce' );
    if ( 'pending' == $order->get_status() ) {
      $order->update_status( 'on-hold', $order_note );
    } else {
      $order->add_order_note( $order_note );
    }

    return true;
  }

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
   * Update subscription status.
   *
   * @param int    $order_id
   * @param string $invoice_status
   *
   * @return bool
   */
  protected function update_subscription_status($order_id, $invoice_status) {

    $order = new WC_Order($order_id);

    $invoice_status = strtolower($invoice_status);

    $order_updated  = false;

    /**
     * Paid Invoice
     */
    if ($invoice_status == 'paid') {

      $order->add_order_note(__('iugu: Subscription paid successfully.', 'iugu-woocommerce'));

      /**
       * Payment complete
       */
      $order->payment_complete();

      $order_updated = true;

    } // end if;

    /**
     * Refund Invoice
     */
    if ($invoice_status == 'canceled') {

      $order->add_order_note(__('iugu: Subscription paid successfully.', 'iugu-woocommerce'));

      /**
       * Payment complete
       */
      $order->payment_complete();

      $order_updated = true;

    } // end if;

    if ( in_array( $invoice_status, array('canceled', 'refunded', 'expired')) ) {

      $order->add_order_note( __( 'iugu: Subscription payment failed.', 'iugu-woocommerce' ) );

      WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );

      $order_updated = true;

    }

    // Allow custom actions when update the order status.
    do_action( 'iugu_woocommerce_update_order_status', $order, $invoice_status, $order_updated );

  }

	/**
	 * Handles API notifications.
	 *
	 * @return void
	 */
	public function notification_handler() {

		$this->api->notification_handler();

	} // end notification_handler;

  /**
	 * Update the customer_id for a subscription
	 *
	 * @param WC_Subscription $subscription The subscription for which the failing payment method relates.
	 * @param WC_Order $renewal_order The order which recorded the successful payment (to make up for the failed automatic payment).
	 * @return void.
	 */
	public function update_failing_payment_method($subscription, $renewal_order) {

		//update_post_meta($subscription->get_id(), '_iugu_customer_payment_method_id', get_post_meta($renewal_order->id, '_iugu_customer_payment_method_id', true));

	} // end update_failing_payment_method;

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

} // end WC_Iugu_Pix_Addons_Gateway;
