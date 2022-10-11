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

class WC_Iugu_Pix_Gateway extends WC_Iugu_Woocommerce_Subscription_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                   = 'iugu-pix';
		parent::__construct();
		global $woocommerce;
		$this->icon                 = apply_filters('iugu_woocommerce_pix_icon', '');
		$this->method_title         = __('iugu - PIX', IUGU);
		$this->method_description   = __('Accept pix payments using iugu.', IUGU);
		$this->supports             = array_merge(
			$this->supports,
			array(
				'refunds',
			)
		);
		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();
		/**
		 * Handles notification requets from the API.
		 */
		add_action('woocommerce_api_wc_iugu_pix_gateway', array($this, 'notification_handler'));
		/**
		 * Adds the gateways settings.
		 */
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		/**
		 * Adds a custom thank you page.
		 */
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
		/**
		 * Adds emails instructions for when the order was placed.
		 */
		add_action('woocommerce_email_after_order_table', array($this, 'email_instructions'), 10, 3);
		add_filter('woocommerce_my_account_my_orders_actions', array($this, 'my_orders_pix_link'), 10, 2);
	} // end __construct;

	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields['enabled'] = array(
			'title'   => __('Enable/Disable', IUGU),
			'type'    => 'checkbox',
			'label'   => __('Enable pix payments with iugu', IUGU),
			'default' => 'no'
		);
		$this->form_fields['title'] = array(
			'title'       => __('Title', IUGU),
			'type'        => 'text',
			'description' => __('Payment method title seen on the checkout page.', IUGU),
			'default'     => __('PIX', IUGU)
		);
		$this->form_fields['description'] = array(
			'title'       => __('Description', IUGU),
			'type'        => 'textarea',
			'description' => __('Payment method description seen on the checkout page.', IUGU),
			'default'     => __('Pay with PIX', IUGU)
		);
		$this->form_fields['ignore_due_email'] = array(
			'title'   => __('Ignore due email', IUGU),
			'type'    => 'checkbox',
			'label'   => __('When checked, Iugu will not send billing emails to the payer', IUGU),
			'default' => 'no'
		);
		$this->form_fields['behavior'] = array(
			'title'       => __('Integration behavior', IUGU),
			'type'        => 'title',
			'description' => ''
		);
		$this->form_fields['send_only_total'] = array(
			'title'   => __('Send only the order total', IUGU),
			'type'    => 'checkbox',
			'label'   => __('When enabled, the customer only gets the order total, not the list of purchased items.', IUGU),
			'default' => 'no'
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
		$order = new WC_Order($order_id);
		$template_data = array();
		if (isset($data['qrcode'])) {
			$template_data['qrcode'] = $data['qrcode'];
			$template_data['qrcode_text'] = $data['qrcode_text'];
			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script('iugu-qrcode', plugins_url('assets/js/qrcode.min.js', WC_IUGU_PLUGIN_FILE), array(), WC_Iugu::CLIENT_VERSION, true);
			wp_enqueue_script('wc-iugu-clipboard', 'https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js', '2.0.8', array('jquery'), WC_Iugu::CLIENT_VERSION, true);
			wp_enqueue_script('iugu-pix', plugins_url('assets/js/pix' . $suffix . '.js', WC_IUGU_PLUGIN_FILE), array('jquery', 'iugu-qrcode', 'wc-iugu-clipboard'), WC_Iugu::CLIENT_VERSION, true);
			wp_enqueue_style('iugu-woocommerce-pix-css', plugins_url('assets/css/pix' . $suffix . '.css', WC_IUGU_PLUGIN_FILE));
			wc_get_template(
				'pix/payment-instructions.php',
				$template_data,
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

	/**
	 * Add bank slip link/button in My Orders section on My Accout page.
	 *
	 * @deprecated 1.1.0
	 */
	public function my_orders_pix_link($actions, $order) {
		if ('iugu-pix' !== $order->get_payment_method()) {
			return $actions;
		}
		if (!in_array($order->get_status(), array('pending'), true)) {
			return $actions;
		}
		array_unshift(
			$actions,
			array(
				'url'  => $this->get_return_url($order),
				'name' => __('Pay PIX', IUGU),
			)
		);
		if (isset($actions['pay']) && isset($actions['pay']['name'])) {
			$actions['pay']['name'] = __('Pay / Generate new PIX', IUGU);
		}
		return $actions;
	}
} // end WC_Iugu_Pix_Gateway;
