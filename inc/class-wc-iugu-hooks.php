<?php
/**
 * iugu My Account actions
 */

if (!defined('ABSPATH')) {

	exit;

} // end if;

class WC_Iugu_Hooks {

  /**
   * Iugu API object.
   *
   * @var object
   */
  protected $api;

	/**
	 * Initialize my account actions.
	 */
	public function __construct() {

    $this->api = new WC_Iugu_API();

		if (defined('WC_VERSION') && version_compare(WC_VERSION, '3.0', '<')) {

			add_filter('woocommerce_my_account_my_orders_actions', array($this, 'legacy_my_orders_bank_slip_link'), 10, 2);

		} else {

			add_filter('woocommerce_my_account_my_orders_actions', array($this, 'legacy_my_orders_bank_slip_link' ), 10, 2);

		} // end if;

		if (class_exists('WC_Subscriptions_Order')) {

			add_filter('woocommerce_subscription_settings', array($this, 'add_woocommerce_subscriptions_settings'), 10, 1);

      $maybe_iugu_handle_subscriptions = get_option('enable_iugu_handle_subscriptions');

      if ($maybe_iugu_handle_subscriptions === 'yes') {

				add_action('wp_ajax_wc_iugu_notification_handler', array($this, 'webhook_notification_handler'));

				add_action('wp_ajax_nopriv_wc_iugu_notification_handler', array($this, 'webhook_notification_handler'));

				if (!get_option('_wc_iugu_webhook_created')) {

					$this->create_iugu_webhook();

				} // end if;

			/**
			 * When the product-subscription is created
			 */
			add_action('woocommerce_process_product_meta_subscription', array($this, 'create_new_iugu_plan'), 99, 1);

			add_action('woocommerce_update_product', array($this, 'create_new_iugu_plan'), 99, 1);

			/**
			 * When the product-subscription is update
			 */
			add_action('before_delete_post', array($this, 'delete_iugu_plan'), 10, 1);

      add_action('add_meta_boxes', array($this, 'iugu_plan_id_meta_box'), 10, 2);

      } // end if

		} // end if;

    add_action('woocommerce_product_options_general_product_data', array($this, 'product_iugu_payment_options'), 10);

		add_filter('woocommerce_available_payment_gateways', array($this, 'filter_gateways_based_on_product'), 10);

    add_action('save_post', array($this, 'saves_product_iugu_payment_options'), 40);

		add_action('woocommerce_payment_token_deleted', array($this, 'remove_payment_method'), 10, 2);

		add_action('woocommerce_before_calculate_totals', array($this, 'add_amount_to_order'), 10, 1);

    add_filter('woocommerce_subscriptions_is_recurring_fee', array($this, 'add_amount_to_subscription_order'), 1500, 3);

	} // end __construct;

	/**
	 * Legacy - Add bank slip link/button in My Orders section on My Accout page.
	 *
	 * @deprecated 1.1.0
	 */
	public function legacy_my_orders_bank_slip_link($actions, $order) {
		if ( 'iugu-bank-slip' !== $order->get_payment_method() ) {
			return $actions;
		}

		if ( ! in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) ) {
			return $actions;
		}

		$data = get_post_meta( $order->get_id(), '_iugu_wc_transaction_data', true );
		if ( ! empty( $data['pdf'] ) ) {
			$actions[] = array(
				'url'  => $data['pdf'],
				'name' => __( 'Pay the bank slip', 'iugu-woocommerce' ),
			);
		}

		return $actions;
	}

	/**
	 * Add bank slip link/button in My Orders section on My Accout page.
	 *
	 * @param array    $actions Link actions.
	 * @param WC_Order $order WooCommerce Order.
	 * @return void
	 */
	public function my_orders_bank_slip_link($actions, $order) {

		if ('iugu-bank-slip' !== $order->get_payment_method()) {

			return $actions;

		} // end if;

		if (!$order->has_status(array('pending', 'on-hold'))) {

			return $actions;

		} // end if;

		$data = $order->get_meta('_iugu_wc_transaction_data');

		if (!empty($data['pdf'])) {

			$actions[] = array(
				'url'  => $data['pdf'],
				'name' => __( 'Pay the bank slip', 'iugu-woocommerce' ),
			);

		} // end if;

		return $actions;

	} // end my_orders_bank_slip_link;

	/**
	 * Adds the option to let iugu handle the subscriptions.
	 *
	 * @since 2.20
	 *
	 * @return void
	 */
	public function add_woocommerce_subscriptions_settings($settings) {

		return array_merge(
			$settings,
			array(
				array(
					'name' => __('Iugu Subscriptions', 'iugu-woocommerce'),
					'type' => 'title',
					'id'   => 'iugu_handle_subscriptions',
				),
				array(
					'name'     => __('Enable Iugu Subscriptions', 'iugu-woocommerce'),
					'desc'     => __('By activating this option, the management of subscriptions will be done by Iugu automatically. Your products will be synced as plans and subscriptions will be created in Iugu.', 'iugu-woocommerce'),
					'id'       => 'enable_iugu_handle_subscriptions',
					'default'  => '',
					'type'     => 'checkbox',
					'desc_tip' => false,
				),
				array(
					'name'     	=> __('Bank Slip discount type', 'iugu-woocommerce'),
					'desc'     	=> __('The subscription using the bank slip has what kind of discount. Persistent, ie every month has the same discount, or only on the first payment?', 'iugu-woocommerce'),
					'id'       	=> 'iugu_subscription_discount_type',
					'default'  	=> 'persistent',
					'type'     	=> 'select',
					'options'   => array(
            'persistent'  => __('Persistent', 'iugu-woocommerce'),
            'one_time'    => __('One Time', 'iugu-woocommerce')
          ),
					'desc_tip'  => false,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'iugu_handle_subscriptions',
				),
			)
		);

		return $settings;

	} // end add_woocommerce_subscriptions_settings;

  /**
   * Create a new plan in Iugu using the product subscription as base.
   *
   * @param string $product_id
   * @return void
   */
  public function create_new_iugu_plan($product_id) {

		if (empty($_POST['_wcsnonce'])) {

				return ;

		} // end if;

		$product = wc_get_product($product_id);

		if ($product->get_status() == 'publish') {

			$product_type = $product->get_type();

			if ($product_type === 'subscription' || $product_type === 'variable-subscription') {

				$product_data = get_post_meta($product_id);

				if (isset($product_data)) {

					$_subscription_period = $product_data['_subscription_period'][0];

					if ($_subscription_period === 'month') {

						$_subscription_period = 'months';

						$_subscription_period_interval = $product_data['_subscription_period_interval'][0];

					} // end if;

					if ($_subscription_period === 'year') {

						$_subscription_period = 'months';

						$_subscription_period_interval = 12;

					} // end if;

					$sale_price = $product->get_sale_price();

					if ($sale_price) {

						$subscription_price = $sale_price;

					} else {

						$subscription_price = $product->get_price();

					} // end if;

				} // end if;

        $plan_params = array(
          "name"          => $product->get_name(),
          "interval"      => isset($_subscription_period_interval) ? $_subscription_period_interval : 1,
          "interval_type" => isset($_subscription_period) ? $_subscription_period : 'months',
          "value_cents"   => $this->api->get_cents($subscription_price),
          "payable_with"  => isset($product_data['_iugu_payable_with'][0]) ? $product_data['_iugu_payable_with'][0] : 'all',
					'billing_days'	=> 5
        );

				if (!isset($product_data['_iugu_plan_id']) || !$product_data['_iugu_plan_id'][0]) {

					$plan_params['identifier'] = trim($product->get_name() . '_' . $product->get_id());

					$response = $this->api->create_iugu_plan($plan_params);

          if (!isset($response['errors'])) {

            update_post_meta($product_id, '_iugu_plan_id', $response['id']);

          } // end if;

				} else {

          $response = $this->api->update_iugu_plan($plan_params, $product_data['_iugu_plan_id'][0]);

				} // end if;

			} // end if;

		} // end if;

	} // end create_new_iugu_plan;

	/**
	 * Deletes a Iugu Plan when te product is deleted.
	 *
	 * @param mixed $product_id WooCommerce Product ID.
	 * @return void
	 */
	public function delete_iugu_plan($product_id){

		$product = wc_get_product($product_id);

		if ($product && $product->get_status() == 'publish') {

			$product_type = $product->get_type();

			if ($product_type === 'subscription' || $product_type === 'variable-subscription') {

				$product_data = get_post_meta($product_id);

        if (isset($product_data['_iugu_plan_id'][0])) {

          $plan_id = $product_data['_iugu_plan_id'][0];

          $response = $this->api->delete_iugu_plan($plan_id);

        } // end if;

		  } // end if;

    } // end if;

  } // delete_iugu_plan;

  /**
   * Create a payment method filter option.
   *
   * @return void
   */
	public function product_iugu_payment_options(){

		woocommerce_wp_select( array(
			'id'          => '_iugu_payable_with',
			'class'       => 'wc_input_subscription_length select short',
			'label'       => __('Avaiable Iugu Payments', 'iugu-woocommerce'),
			'options'     => array(
				'all'			          => __('All', 'iugu-woocommerce'),
				'iugu-bank-slip'		=> __('Bank Slip', 'iugu-woocommerce'),
				'iugu-credit-card'	=> __('Credit Card', 'iugu-woocommerce'),
        'iugu-pix'          => __('PIX', 'iugu-woocommerce')
			),
			'desc_tip'    => true,
			'description' => '',
			)
		);

	} // end product_iugu_payment_options

  /**
   * Saves product option;
   *
   * @param string $product_id Product ID.
   * @return void
   */
	public function saves_product_iugu_payment_options($product_id) {

		if (empty($_POST['_wpnonce'])) {

			return ;

		} // end if;

		$product = get_post_type($product_id);

		if ($product == 'product') {

			update_post_meta($product_id, '_iugu_payable_with', $_POST['_iugu_payable_with']);

		} // end if;

	} // end saves_options_iugu_plan;

  /**
   * Adds the iugu plan id meta box in the product page.
   *
   * @since 2.20
   *
   * @return void
   */
  public function iugu_plan_id_meta_box($post_type, $post) {

		$maybe_iugu_handle_subscriptions = get_option('enable_iugu_handle_subscriptions');

		if ($maybe_iugu_handle_subscriptions === 'yes') {

			add_meta_box('iugu_plan_id_meta_box', __('Iugu Gateway', 'iugu-woocommerce'), array($this, 'output_iugu_plan_id_meta_box'), 'product', 'side', 'default');

			add_meta_box('iugu_subscription_meta_box', __('Iugu Gateway', 'iugu-woocommerce'), array($this, 'output_iugu_subscription_meta_box'), 'shop_subscription', 'side', 'default');

		} // end if;

  } // end iugu_plan_id_meta_box;

  /**
   * Outputs the content of the iugu plan id meta box in the product page.
   *
   * @since 2.20
   *
   * @param WP_Post $post
   * @return void.
   */
  public function output_iugu_plan_id_meta_box($post) {

    $product_meta = get_post_meta($post->ID);

    if (isset($product_meta['_iugu_plan_id']) && $product_meta['_iugu_plan_id'][0]) {

    ?>
      <div class="col-sm-12">

			<h4><?php echo __('Plan ID'); ?></h4>

        <input type="text" name="_iugu_plan_id_metabox" style="width: 100%;font-size: 12px;color: #333333;" id="_iugu_plan_id_metabox" value="<?php echo $product_meta['_iugu_plan_id'][0]; ?>" disabled>

      </div>

    <?php } else { ?>

      <div class="col-sm-12">

        <span><?php _e('No plan for this product', 'iugu-woocommerce'); ?></span>

      </div>

    <?php

    } // end if;

  } // end output_iugu_plan_id_meta_box;

	/**
   * Outputs the content of the iugu subscription meta box in the subscription page.
   *
   * @since 2.20
   *
   * @param WP_Post $post
   * @return void.
   */
  public function output_iugu_subscription_meta_box($post) {

    $product_meta = get_post_meta($post->ID);

    if (isset($product_meta['_wcs_iugu_subscription_id']) && $product_meta['_wcs_iugu_subscription_id'][0]) {

    ?>
      <div class="col-sm-12">

        <h4><?php echo __('Subscription ID'); ?></h4>

        <input type="text" name="_iugu_subscription_id_metabox" style="width: 100%;font-size: 12px;color: #333333;" id="_iugu_subscription_id_metabox" value="<?php echo $product_meta['_wcs_iugu_subscription_id'][0]; ?>" disabled>

      </div>

    <?php } else { ?>

      <div class="col-sm-12">

        <span><?php _e('No subscription for this product', 'iugu-woocommerce'); ?></span>

      </div>

    <?php

    } // end if;

  } // end output_iugu_subscription_meta_box;

	/**
	 * Deletes a payment method when the user clicks in the Delete button.
	 *
	 * @param string $token_id The token ID.
	 * @param object $token    The Token object.
	 * @return void
	 */
  public function remove_payment_method($token_id,  $token) {

   	$customer_id = get_user_meta(get_current_user_id(), '_iugu_customer_id',true);

		$this->api->delete_customer_payment_method($customer_id, $token->get_token());

  } // end remove_payment_method;

	/**
	 * Filter gateways based on the payable with option.
	 *
	 * @param array $gateways
	 * @return array.
	 */
	public function filter_gateways_based_on_product($gateways) {

		if (WC()->cart) {

			$filtered_gateways = array();

			foreach (WC()->cart->get_cart() as $cart_item) {

				$product_id = $cart_item['data']->get_id();
				$payable_with = get_post_meta($product_id, '_iugu_payable_with', true);
				if ($payable_with == null) {
					$payable_with = 'all';
				}
				$filtered_gateways[$payable_with] = $payable_with;

			} // end if;


			if (isset($filtered_gateways['all'])) {

				return $gateways;

			} // end if;

			foreach ($filtered_gateways as $filter_gateway_key) {

				$filtered_gateways[$filter_gateway_key] = $gateways[$filter_gateway_key];

			} // end foreach;

			return $filtered_gateways;

		} // end if;

		return $gateways;

	} // end filter_gateways_based_on_product;

	/**
	 * Add metadata amount to an order and set new total order
	 *
	 * @param object $cart
	 * @return void
	 */
	public function add_amount_to_order($cart) {

		if (WC()->session->chosen_payment_method === 'iugu-bank-slip') {

			$iugu_options = get_option('woocommerce_iugu-bank-slip_settings');

			if (isset($iugu_options['enable_discount']) and $iugu_options['enable_discount']) {

				$type = $iugu_options['discount_type'];

				$discount_value = $iugu_options['discount_value'];

				if ($type == 'percentage') {

					foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {

						$subtotal = $cart_item['line_total'];

					} // end foreach;

					$value = ($subtotal / 100) * $discount_value;

				} else {

					$discount_value = $discount_value;

					$value = $discount_value;

				} // end if;

			} // end if;

			if ($value) {

				$cart->add_fee(__('Bank Slip discount', 'iugu-woocommerce'), -$value);

			} // end if ;

		} // end if;

	} // end add_amount_to_order;

  public function add_amount_to_subscription_order($false = false, $fee, $cart) {

    $maybe_iugu_handle_subscriptions = get_option('enable_iugu_handle_subscriptions');

    $iugu_subscription_discount_type = get_option('iugu_subscription_discount_type');

    if ($maybe_iugu_handle_subscriptions === 'yes') {

      if ($iugu_subscription_discount_type !== 'persistent') {

        $cart->fees_api()->remove_all_fees();

        $value = 0;

        $cart->add_fee(__('Bank Slip discount', 'iugu-woocommerce'), -$value);

      } // end if;

    } // end if;

    return false;

  } // end add_amount_to_subscription_order;

	/**
	 * Create iugu webhook for subscriptions that are handled by Iugu.
	 *
	 * @return void
	 */
	public function create_iugu_webhook() {

		if (!get_option('_wc_iugu_webhook_created')) {

			$webhook = $this->api->create_iugu_webhook();

			if (isset($webhook['id'])) {

				update_option('_wc_iugu_webhook_created', $webhook['id']);

			} // end if;

		} // end if;

	} // end create_iugu_webhook;

	/**
	 * Handles notifications received via ajax endpoint.
	 *
	 * @return void.
	 */
	public function webhook_notification_handler() {

		$this->api->notification_handler();

	} // end webhook_notification_handler;

} // end WC_Iugu_Hooks;
