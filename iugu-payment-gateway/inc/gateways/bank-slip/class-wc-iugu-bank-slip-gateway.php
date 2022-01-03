<?php

if (!defined('ABSPATH')) {

	exit;
} // end if;

/**
 * iugu Payment Bank Slip Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class   WC_Iugu_Bank_Slip_Gateway2
 * @extends WC_Payment_Gateway 
 * @version 1.0.0
 * @author  iugu
 */
class WC_Iugu_Bank_Slip_Gateway2 extends WC_Iugu_Woocommerce_Subscription_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                   = 'iugu-bank-slip';
		parent::__construct();
		global $woocommerce;
		$this->icon                 = apply_filters('iugu_woocommerce_bank_slip_icon', '');
		$this->method_title         = __('iugu - Bank slip', 'iugu-woocommerce');
		$this->method_description   = __('Accept bank slip payments using iugu.', 'iugu-woocommerce');
		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();
		// Options.
		$this->enable_discount  = $this->get_option('enable_discount') == 'yes';
		$this->discount_type    = $this->get_option('discount_type');
		$this->discount_value   = $this->get_option('discount_value');
		$this->deadline         = $this->get_option('deadline');
		// Actions.
		add_action('woocommerce_api_wc_iugu_bank_slip_gateway', array($this, 'notification_handler'));
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
		add_action('woocommerce_email_after_order_table', array($this, 'email_instructions'), 10, 3);
		add_filter('woocommerce_my_account_my_orders_actions', array($this, 'my_orders_bank_slip_link'), 10, 2);
		if ($this->enable_discount) {
			add_filter('woocommerce_gateway_title', array($this, 'discount_payment_method_title'), 10, 2);
		} // end if;
	} // end __construct;

	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __('Enable/Disable', 'iugu-woocommerce'),
				'type'    => 'checkbox',
				'label'   => __('Enable bank slip payments with iugu', 'iugu-woocommerce'),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __('Title', 'iugu-woocommerce'),
				'type'        => 'text',
				'description' => __('Payment method title seen on the checkout page.', 'iugu-woocommerce'),
				'default'     => __('Bank slip', 'iugu-woocommerce')
			),
			'description' => array(
				'title'       => __('Description', 'iugu-woocommerce'),
				'type'        => 'textarea',
				'description' => __('Payment method description seen on the checkout page.', 'iugu-woocommerce'),
				'default'     => __('Pay with bank slip', 'iugu-woocommerce')
			),
			'integration' => array(
				'title'       => __('Integration settings', 'iugu-woocommerce'),
				'type'        => 'title',
				'description' => ''
			),
			'account_id' => array(
				'title'             => __('Account ID', 'iugu-woocommerce'),
				'type'              => 'text',
				'description'       => sprintf(__('Your iugu account\'s unique ID, found in %s.', 'iugu-woocommerce'), '<a href="https://app.iugu.com/account" target="_blank">' . __('iugu account settings', 'iugu-woocommerce') . '</a>'),
				'default'           => '',
				'custom_attributes' => array(
					'required' => 'required'
				)
			),
			'api_token' => array(
				'title'            => __('API Token', 'iugu-woocommerce'),
				'type'              => 'text',
				'description'       => sprintf(__('For real payments, use a LIVE API token. When iugu sandbox is enabled, use a TEST API token. API tokens can be found/created in %s.', 'iugu-woocommerce'), '<a href="https://app.iugu.com/account" target="_blank">' . __('iugu account settings', 'iugu-woocommerce') . '</a>'),
				'default'           => '',
				'custom_attributes' => array(
					'required' => 'required'
				)
			),
			'ignore_due_email' => array(
				'title'            => __('Ignore due email', 'iugu-woocommerce'),
				'type'              => 'checkbox',
				'label'       => __('When checked, Iugu will not send billing emails to the payer', 'iugu-woocommerce'),
				'default'           => 'no'
			),
			'payment' => array(
				'title'       => __('Payment options', 'iugu-woocommerce'),
				'type'        => 'title',
				'description' => ''
			),
			'enable_discount' => array(
				'title'       => __('Enable Bank Slip Discount', 'iugu-woocommerce'),
				'type'        => 'checkbox',
				'label'       => __('A discount for customers who choose this payment method.', 'iugu-woocommerce'),
				'default'     => 'no',
				'description' => __('A discount for customers who choose this payment method.', 'iugu-woocommerce'),
			),
			'discount_type' => array(
				'title'             => __('Discount Type', 'iugu-woocommerce'),
				'type'              => 'select',
				'description'       => __('Discount can be a percentage amount or a fixed amount of the total order.', 'iugu-woocommerce'),
				'default'           => 'percentage',
				'options' => array(
					'percentage' => __('(%) Percentage'),
					'fixed'      => __('Fixed Amount')
				),
			),
			'discount_value' => array(
				'title'             => __('Discount value', 'iugu-woocommerce'),
				'type'              => 'number',
				'description'       => __('The amount of discount.', 'iugu-woocommerce'),
				'default'           => '0',
			),
			'deadline' => array(
				'title'             => __('Default payment deadline', 'iugu-woocommerce'),
				'type'              => 'number',
				'description'       => __('Number of days the customer will have to pay the bank slip.', 'iugu-woocommerce'),
				'default'           => '5',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '1'
				)
			),
			'behavior' => array(
				'title'       => __('Integration behavior', 'iugu-woocommerce'),
				'type'        => 'title',
				'description' => ''
			),
			'send_only_total' => array(
				'title'   => __('Send only the order total', 'iugu-woocommerce'),
				'type'    => 'checkbox',
				'label'   => __('When enabled, the customer only gets the order total, not the list of purchased items.', 'iugu-woocommerce'),
				'default' => 'no'
			),
			'testing' => array(
				'title'       => __('Gateway testing', 'iugu-woocommerce'),
				'type'        => 'title',
				'description' => ''
			),
			'sandbox' => array(
				'title'       => __('iugu sandbox', 'iugu-woocommerce'),
				'type'        => 'checkbox',
				'label'       => __('Enable iugu sandbox', 'iugu-woocommerce'),
				'default'     => 'no',
				'description' => sprintf(__('Used to test payments. Don\'t forget to use a TEST API token, which can be found/created in %s.', 'iugu-woocommerce'), '<a href="https://iugu.com/settings/account" target="_blank">' . __('iugu account settings', 'iugu-woocommerce') . '</a>')
			),
			'debug' => array(
				'title'       => __('Debugging', 'iugu-woocommerce'),
				'type'        => 'checkbox',
				'label'       => __('Enable logging', 'iugu-woocommerce'),
				'default'     => 'no',
				'description' => sprintf(__('Log iugu events, such as API requests, for debugging purposes. The log can be found in %s.', 'iugu-woocommerce'), WC_Iugu2::get_log_view($this->id))
			)
		);
	} // end init_form_fields;

	/**
	 * Payment fields.
	 *
	 * @return void
	 */
	public function payment_fields() {
		if ($description = $this->get_description()) {
			echo wpautop(wptexturize($description));
		} // end if;
		if ($this->enable_discount) {
			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script('iugu-bank-slip', plugins_url('assets/js/bank-slip' . $suffix . '.js', WC_IUGU_PLUGIN_FILE), array('jquery'), WC_Iugu2::CLIENT_VERSION, true);
		} // end if;
		wc_get_template(
			'bank-slip/checkout-instructions.php',
			array(),
			'woocommerce/iugu/',
			WC_Iugu2::get_templates_path()
		);
	} // end payment_fields;

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int $order_id Order ID.
	 * @return array         Redirect.
	 */
	public function process_payment($order_id) {
		return $this->api->process_payment($order_id);
	} // end process_payment;

	/**
	 * Thank You page message.
	 *
	 * @param  int    $order_id Order ID.
	 *
	 * @return string
	 */
	public function thankyou_page($order_id) {
		$data = get_post_meta($order_id, '_iugu_wc_transaction_data', true);
		if (isset($data['pdf'])) {
			wc_get_template(
				'bank-slip/payment-instructions.php',
				array(
					'pdf' => $data['pdf']
				),
				'woocommerce/iugu/',
				WC_Iugu2::get_templates_path()
			);
		}
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param  object $order         Order object.
	 * @param  bool   $sent_to_admin Send to admin.
	 * @param  bool   $plain_text    Plain text or HTML.
	 *
	 * @return string                Payment instructions.
	 */
	public function email_instructions($order, $sent_to_admin, $plain_text = false) {
		if ($sent_to_admin || !in_array($order->get_status(), array('processing', 'on-hold')) || $this->id !== $order->get_payment_method()) {
			return;
		}
		$data = get_post_meta($order->get_id(), '_iugu_wc_transaction_data', true);
		if (isset($data['pdf'])) {
			if ($plain_text) {
				wc_get_template(
					'bank-slip/emails/plain-instructions.php',
					array(
						'pdf' => $data['pdf']
					),
					'woocommerce/iugu/',
					WC_Iugu2::get_templates_path()
				);
			} else {
				wc_get_template(
					'bank-slip/emails/html-instructions.php',
					array(
						'pdf' => $data['pdf']
					),
					'woocommerce/iugu/',
					WC_Iugu2::get_templates_path()
				);
			}
		}
	}

	/**
	 * Handles API Notifications.
	 *
	 * @return void
	 */
	public function notification_handler() {
		$this->api->notification_handler();
	} // end notification_handler;

	/**
	 * Add bank slip link/button in My Orders section on My Accout page.
	 *
	 * @deprecated 1.1.0
	 */
	public function my_orders_bank_slip_link($actions, $order) {
		if ('iugu-bank-slip' !== $order->get_payment_method()) {
			return $actions;
		}
		if (!in_array($order->get_status(), array('pending'), true)) {
			return $actions;
		}
		$data = get_post_meta($order->get_id(), '_iugu_wc_transaction_data', true);
		if (!empty($data['pdf'])) {
			array_unshift(
				$actions,
				array(
					'url'  => $data['pdf'],
					'name' => __('Pay the bank slip', 'iugu-woocommerce'),
				)
			);
			if (isset($actions['pay']) && isset($actions['pay']['name'])) {
				$actions['pay']['name'] = __('Pay / Generate new bank slip', 'iugu-woocommerce');
			}
		}
		return $actions;
	}

	/**
	 * Display the discount in the gateway title.
	 *
	 * @param string $title
	 * @param string $id
	 * @return void
	 */
	public function discount_payment_method_title($title, $gateway_id) {
		if (!is_checkout() && !(defined('DOING_AJAX') && DOING_AJAX)) {
			return $title;
		} // end if;
		if ($gateway_id === $this->id) {
			if ($this->discount_value && 0 < $this->discount_value) {
				if ($this->discount_type && $this->discount_type == 'percentage') {
					$value = $this->discount_value . '%';
				} else {
					$value = wc_price($this->discount_value);
				} // end if;
				$persistent = true;
				if (function_exists('wcs_cart_contains_renewal')) {
					$cart_item = wcs_cart_contains_renewal();
					if (false !== $cart_item && isset($cart_item['subscription_renewal']['renewal_order_id'])) {
						$renewal_order = wc_get_order($cart_item['subscription_renewal']['renewal_order_id']);
						if ($renewal_order) {
							$iugu_subscription_discount_type = get_option('iugu_subscription_discount_type');
							$persistent = $iugu_subscription_discount_type === 'persistent';
						}
					}
				}
				if ($persistent) {
					$title .= '(' . sprintf(__('%s off', 'iugu-woocommerce'), $value) . ')';
				}
			} // end if;
			return $title;
		} // end if;
		return $title;
	} // end discount_payment_method_title;

} // end WC_Iugu_Bank_Slip_Gateway2;
