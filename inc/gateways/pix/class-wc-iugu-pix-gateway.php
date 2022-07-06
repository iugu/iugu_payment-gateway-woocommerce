<?php
/**
 * Iugu Payment Bank Slip Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class   WC_Iugu_Pix_Gateway
 * @extends WC_Payment_Gateway
 * @version 2.2.0
 * @author  iugu
 */

if (!defined('ABSPATH')) {

	exit;

} // end if;

class WC_Iugu_Pix_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {

		global $woocommerce;

		$this->id                   = 'iugu-pix';
		$this->icon                 = apply_filters('iugu_woocommerce_pix_icon', '');
		$this->method_title         = __('iugu - PIX', 'iugu-woocommerce');
		$this->method_description   = __('Accept pix payments using iugu.', 'iugu-woocommerce');
		$this->has_fields           = true;
		$this->view_transaction_url = 'https://iugu.com/a/invoices/%s';
		$this->supports             = array(
			'subscriptions',
			'products',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_amount_changes',
			'subscription_payment_method_change', // Subscriptions 1.n compatibility.
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			'subscription_date_changes',
			'pre-orders',
			'refunds'
		);

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Options.
		$this->title            = $this->get_option('title');
		$this->description      = $this->get_option('description');
		$this->account_id       = $this->get_option('account_id');
		$this->api_token        = $this->get_option('api_token');
		$this->ignore_due_email = $this->get_option('ignore_due_email');
		$this->send_only_total  = $this->get_option('send_only_total', 'no');
		$this->sandbox          = $this->get_option('sandbox', 'no');
		$this->debug            = $this->get_option('debug');

		/**
		 * Active logs.
		 */
		if ('yes' == $this->debug) {

			if (class_exists('WC_Logger')) {

				$this->log = new WC_Logger();

			} else {

				$this->log = $woocommerce->logger();

			} // end if;

		} // end if;

		/**
		 * iugu API
		 */
		$this->api = new WC_Iugu_API($this, 'pix');

		/**
		 * Handles notification requets from the API.
		 */
		add_action('woocommerce_api_wc_iugu_pix_gateway', array($this, 'notification_handler'));

		/**
		 * Adds the gateways settings.
		 */
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options'));

		/**
		 * Adds a custom thank you page.
		 */
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

		/**
		 * Adds emails instructions for when the order was placed.
		 */
		add_action('woocommerce_email_after_order_table', array($this, 'email_instructions'), 10, 3);

	} // end __construct;

	/**
	 * Returns a value indicating the the Gateway is available or not.
	 *
	 * @return bool
	 */
	public function is_available() {

		// Test if is valid for use.
		$api = !empty($this->account_id) && ! empty($this->api_token);

		$available = 'yes' == $this->get_option('enabled') && $api && $this->api->using_supported_currency();

		return $available;

	} // end is_available;

	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {

		$this->form_fields['enabled'] = array(
			'title'   => __('Enable/Disable', 'iugu-woocommerce'),
			'type'    => 'checkbox',
			'label'   => __('Enable pix payments with iugu', 'iugu-woocommerce'),
			'default' => 'no'
		);

		$this->form_fields['title'] = array(
			'title'       => __('Title', 'iugu-woocommerce'),
			'type'        => 'text',
			'description' => __('Payment method title seen on the checkout page.', 'iugu-woocommerce'),
			'default'     => __('PIX', 'iugu-woocommerce')
		);

		$this->form_fields['description'] = array(
			'title'       => __('Description', 'iugu-woocommerce'),
			'type'        => 'textarea',
			'description' => __('Payment method description seen on the checkout page.', 'iugu-woocommerce'),
			'default'     => __('Pay with PIX.', 'iugu-woocommerce')
		);

		$this->form_fields['integration'] = array(
			'title'       => __('Integration settings', 'iugu-woocommerce'),
			'type'        => 'title',
			'description' => ''
		);

		$this->form_fields['account_id'] = array(
			'title'             => __('Account ID', 'iugu-woocommerce'),
			'type'              => 'text',
			'description'       => sprintf(__( 'Your iugu account\'s unique ID, found in %s.', 'iugu-woocommerce' ), '<a href="https://app.iugu.com/account" target="_blank">' . __( 'iugu account settings', 'iugu-woocommerce' ) . '</a>'),
			'default'           => '',
			'custom_attributes' => array(
				'required' => 'required'
			)
		);

		$this->form_fields['api_token'] = array(
			'title'             => __('API Token', 'iugu-woocommerce'),
			'type'              => 'text',
			'description'       => sprintf(__( 'For real payments, use a LIVE API token. When iugu sandbox is enabled, use a TEST API token. API tokens can be found/created in %s.', 'iugu-woocommerce' ), '<a href="https://app.iugu.com/account" target="_blank">' . __( 'iugu account settings', 'iugu-woocommerce' ) . '</a>'),
			'default'           => '',
			'custom_attributes' => array(
				'required' => 'required'
			)
		);

		$this->form_fields['ignore_due_email'] = array(
			'title'   => __('Ignore due email', 'iugu-woocommerce'),
			'type'    => 'checkbox',
			'label'   => __('When checked, Iugu will not send billing emails to the payer', 'iugu-woocommerce'),
			'default' => 'no'
		);

		$this->form_fields['behavior'] = array(
			'title'       => __('Integration behavior', 'iugu-woocommerce'),
			'type'        => 'title',
			'description' => ''
		);

		$this->form_fields['send_only_total'] = array(
			'title'   => __('Send only the order total', 'iugu-woocommerce'),
			'type'    => 'checkbox',
			'label'   => __('When enabled, the customer only gets the order total, not the list of purchased items.', 'iugu-woocommerce'),
			'default' => 'no'
		);

		$this->form_fields['testing'] = array(
			'title'       => __('Gateway testing', 'iugu-woocommerce'),
			'type'        => 'title',
			'description' => ''
		);

		$this->form_fields['sandbox'] = array(
			'title'       => __('iugu sandbox', 'iugu-woocommerce'),
			'type'        => 'checkbox',
			'label'       => __('Enable iugu sandbox', 'iugu-woocommerce'),
			'default'     => 'no',
			'description' => sprintf(__('Used to test payments. Don\'t forget to use a TEST API token, which can be found/created in %s.', 'iugu-woocommerce'), '<a href="https://iugu.com/settings/account" target="_blank">' . __('iugu account settings', 'iugu-woocommerce') . '</a>')
		);

	} // end init_form_fields;

	/**
	 * Pix payment field.
	 *
	 * @return void
	 */
	public function payment_fields() {

		if ($description = $this->get_description()) {

			echo wpautop(wptexturize($description));

		} // end if;

		wc_get_template(
			'pix/checkout-instructions.php',
			array(),
			'woocommerce/iugu/',
			WC_Iugu::get_templates_path()
		);

	} // end payment_fields;

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int $order_id WooCommerce Order ID.
	 * @return array Redirect.
	 */
	public function process_payment($order_id) {

		return $this->api->process_payment($order_id);

	} // end process_payment;

	/**
	 * Thank You page message.
	 *
	 * @param  int $order_id WooCommerce Order ID.
	 * @return void
	 */
	public function thankyou_page($order_id) {

		$data = get_post_meta($order_id, '_iugu_wc_transaction_data', true);

		if (isset($data['qrcode'])) {

			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script('iugu-qrcode', plugins_url('assets/js/qrcode.min.js', WC_IUGU_PLUGIN_FILE), array(), WC_Iugu::CLIENT_VERSION, true);

			wp_enqueue_script('wc-iugu-clipboard', 'https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js', '2.0.8', array('jquery'), WC_Iugu::CLIENT_VERSION, true);

			wp_enqueue_script('iugu-credit-pix', plugins_url('assets/js/pix' . $suffix . '.js', WC_IUGU_PLUGIN_FILE), array('jquery', 'iugu-qrcode', 'wc-iugu-clipboard'), WC_Iugu::CLIENT_VERSION, true);

			wp_enqueue_style('iugu-woocommerce-pix-css', plugins_url('assets/css/pix' . $suffix . '.css', WC_IUGU_PLUGIN_FILE));

			wc_get_template(
				'pix/payment-instructions.php',
				array(
					'qrcode'      => $data['qrcode'],
					'qrcode_text' => $data['qrcode_text'],
				),
				'woocommerce/iugu/',
				WC_Iugu::get_templates_path()
			);

		} // end if;

	} // end thankyou_page;

	/**
	 * Add content to the WC emails.
	 *
	 * @param  object $order         Order object.
	 * @param  bool   $sent_to_admin Send to admin.
	 * @param  bool   $plain_text    Plain text or HTML.
	 * @return string Payment instructions.
	 */
	public function email_instructions($order, $sent_to_admin, $plain_text = false) {

		if ($sent_to_admin || !in_array($order->get_status(), array('processing', 'on-hold')) || $this->id !== $order->get_payment_method()) {

			return;

		} // end if;

		$data = get_post_meta($order->get_id(), '_iugu_wc_transaction_data', true);

		if (isset($data['pdf'])) {

			if ($plain_text) {

				wc_get_template(
					'pix/emails/plain-instructions.php',
					array(
						'qrcode'      => $data['qrcode'],
						'qrcode_text' => $data['qrcode_text'],
					),
					'woocommerce/iugu/',
					WC_Iugu::get_templates_path()
				);

			} else {

				wc_get_template(
					'pix/emails/html-instructions.php',
					array(
						'qrcode'      => $data['qrcode'],
						'qrcode_text' => $data['qrcode_text'],
					),
					'woocommerce/iugu/',
					WC_Iugu::get_templates_path()
				);

			} // end if;

		} // end if;

	} // end email_instructions;

	/**
	 * Notification handler.
	 *
	 * @return void
	 */
	public function notification_handler() {

		$this->api->notification_handler();

	} // end notification_handler;

	public function process_refund($order_id, $amount = NULL, $reason = '')	{

		return $this->api->refund_order($order_id, $amount);

	} // end process_refund;

} // end WC_Iugu_Pix_Gateway;
