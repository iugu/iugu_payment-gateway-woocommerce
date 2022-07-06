<?php

if (!defined( 'ABSPATH')) {

	exit;

} // end if;

/**
 * iugu Payment Bank Slip Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class   WC_Iugu_Bank_Slip_Gateway
 * @extends WC_Payment_Gateway
 * @version 1.0.0
 * @author  iugu
 */
class WC_Iugu_Bank_Slip_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		global $woocommerce;

		$this->id                   = 'iugu-bank-slip';
		$this->icon                 = apply_filters( 'iugu_woocommerce_bank_slip_icon', '' );
		$this->method_title         = __( 'iugu - Bank slip', 'iugu-woocommerce' );
		$this->method_description   = __( 'Accept bank slip payments using iugu.', 'iugu-woocommerce' );
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
			'pre-orders'
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
		$this->enable_discount  = $this->get_option('enable_discount');
		$this->discount_type    = $this->get_option('discount_type');
		$this->discount_value   = $this->get_option('discount_value');
		$this->deadline         = $this->get_option('deadline');
		$this->send_only_total  = $this->get_option('send_only_total', 'no');
		$this->sandbox          = $this->get_option('sandbox', 'no');
		$this->debug            = $this->get_option('debug');

		// Active logs.
		if ( 'yes' == $this->debug ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = $woocommerce->logger();
			}
		}

		$this->api = new WC_Iugu_API( $this, 'bank-slip' );

		// Actions.
		add_action('woocommerce_api_wc_iugu_bank_slip_gateway', array( $this, 'notification_handler' ) );

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action('woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

		add_action('woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3);

		if ($this->enable_discount) {

			add_filter('woocommerce_gateway_title', array($this, 'discount_payment_method_title' ), 10, 2);

		} // end if;

	} // end __construct;

	/**
	 * Returns a value indicating the the Gateway is available or not. It's called
	 * automatically by WooCommerce before allowing customers to use the gateway
	 * for payment.
	 *
	 * @return bool
	 */
	public function is_available() {
		// Test if is valid for use.
		$api = ! empty( $this->account_id ) && ! empty( $this->api_token );

		$available = 'yes' == $this->get_option( 'enabled' ) && $api && $this->api->using_supported_currency();

		return $available;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'iugu-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable bank slip payments with iugu', 'iugu-woocommerce' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'iugu-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Payment method title seen on the checkout page.', 'iugu-woocommerce' ),
				'default'     => __( 'Bank slip', 'iugu-woocommerce' )
			),
			'description' => array(
				'title'       => __( 'Description', 'iugu-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description seen on the checkout page.', 'iugu-woocommerce' ),
				'default'     => __( 'Pay with bank slip', 'iugu-woocommerce' )
			),
			'integration' => array(
				'title'       => __( 'Integration settings', 'iugu-woocommerce' ),
				'type'        => 'title',
				'description' => ''
			),
			'account_id' => array(
				'title'             => __( 'Account ID', 'iugu-woocommerce' ),
				'type'              => 'text',
				'description'       => sprintf( __( 'Your iugu account\'s unique ID, found in %s.', 'iugu-woocommerce' ), '<a href="https://app.iugu.com/account" target="_blank">' . __( 'iugu account settings', 'iugu-woocommerce' ) . '</a>' ),
				'default'           => '',
				'custom_attributes' => array(
					'required' => 'required'
				)
			),
			'api_token' => array(
				'title'            => __( 'API Token', 'iugu-woocommerce' ),
				'type'              => 'text',
				'description'       => sprintf( __( 'For real payments, use a LIVE API token. When iugu sandbox is enabled, use a TEST API token. API tokens can be found/created in %s.', 'iugu-woocommerce' ), '<a href="https://app.iugu.com/account" target="_blank">' . __( 'iugu account settings', 'iugu-woocommerce' ) . '</a>' ),
				'default'           => '',
				'custom_attributes' => array(
					'required' => 'required'
				)
			),
			'ignore_due_email' => array(
				'title'            => __( 'Ignore due email', 'iugu-woocommerce' ),
				'type'              => 'checkbox',
				'label'       => __( 'When checked, Iugu will not send billing emails to the payer', 'iugu-woocommerce' ),
				'default'           => 'no'
			),
			'payment' => array(
				'title'       => __( 'Payment options', 'iugu-woocommerce' ),
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
				'description'       => __('Discount can be a percentage amount or a fixed amount of the total order.', 'iugu-woocommerce' ),
				'default'           => 'percentage',
				'options' => array(
					'percentage' => __('(%) Percentage'),
					'fixed'      => __('Fixed Amount')
				),
			),
			'discount_value' => array(
				'title'             => __('Discount value', 'iugu-woocommerce' ),
				'type'              => 'number',
				'description'       => __('The amount of discount.', 'iugu-woocommerce' ),
				'default'           => '0',
			),
			'deadline' => array(
				'title'             => __( 'Default payment deadline', 'iugu-woocommerce' ),
				'type'              => 'number',
				'description'       => __( 'Number of days the customer will have to pay the bank slip.', 'iugu-woocommerce' ),
				'default'           => '5',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '1'
				)
			),
			'behavior' => array(
				'title'       => __( 'Integration behavior', 'iugu-woocommerce' ),
				'type'        => 'title',
				'description' => ''
			),
			'send_only_total' => array(
				'title'   => __( 'Send only the order total', 'iugu-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'When enabled, the customer only gets the order total, not the list of purchased items.', 'iugu-woocommerce' ),
				'default' => 'no'
			),
			'testing' => array(
				'title'       => __( 'Gateway testing', 'iugu-woocommerce' ),
				'type'        => 'title',
				'description' => ''
			),
			'sandbox' => array(
				'title'       => __( 'iugu sandbox', 'iugu-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable iugu sandbox', 'iugu-woocommerce' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Used to test payments. Don\'t forget to use a TEST API token, which can be found/created in %s.', 'iugu-woocommerce' ), '<a href="https://iugu.com/settings/account" target="_blank">' . __( 'iugu account settings', 'iugu-woocommerce' ) . '</a>' )
			),
			'debug' => array(
				'title'       => __( 'Debugging', 'iugu-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'iugu-woocommerce' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log iugu events, such as API requests, for debugging purposes. The log can be found in %s.', 'iugu-woocommerce' ), WC_Iugu::get_log_view( $this->id ) )
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

			wp_enqueue_script('iugu-bank-slip', plugins_url('assets/js/bank-slip' . $suffix . '.js', WC_IUGU_PLUGIN_FILE ), array('jquery'), WC_Iugu::CLIENT_VERSION, true);

		} // end if;

		wc_get_template(
			'bank-slip/checkout-instructions.php',
			array(),
			'woocommerce/iugu/',
			WC_Iugu::get_templates_path()
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
	public function thankyou_page( $order_id ) {
		$data = get_post_meta( $order_id, '_iugu_wc_transaction_data', true );

		if ( isset( $data['pdf'] ) ) {
			wc_get_template(
				'bank-slip/payment-instructions.php',
				array(
					'pdf' => $data['pdf']
				),
				'woocommerce/iugu/',
				WC_Iugu::get_templates_path()
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
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $sent_to_admin || ! in_array( $order->get_status(), array( 'processing', 'on-hold' ) ) || $this->id !== $order->get_payment_method() ) {
			return;
		}

		$data = get_post_meta( $order->get_id(), '_iugu_wc_transaction_data', true );

		if ( isset( $data['pdf'] ) ) {
			if ( $plain_text ) {
				wc_get_template(
					'bank-slip/emails/plain-instructions.php',
					array(
						'pdf' => $data['pdf']
					),
					'woocommerce/iugu/',
					WC_Iugu::get_templates_path()
				);
			} else {
				wc_get_template(
					'bank-slip/emails/html-instructions.php',
					array(
						'pdf' => $data['pdf']
					),
					'woocommerce/iugu/',
					WC_Iugu::get_templates_path()
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
	 * Calculate the amount of discount
	 *
	 * @since 2.20
	 *
	 * @param string  $type Discount type.
	 * @param string  $value Discount value.
	 * @param string  $subtotal Order total.
	 * @return string Discount value.
	 */
	protected function calculate_discount($type, $discount_value, $subtotal) {

		$subtotal = $subtotal;

		if ($type == 'percentage') {

			$value = $subtotal - ($subtotal / 100) * ($discount_value);

		} else {

			$discount_value = $discount_value;

			$value = $subtotal - $discount_value;

		} // end if;

		return $value;

	} // end calculate_discount;

	/**
	 * Discount name;
	 *
	 * @since 2.20
	 *
	 * @param string  $type Discount type.
	 * @param string  $value Discount value.
	 * @return string Discount name.
	 */
	protected function discount_name($type, $value) {

		if ($type === 'percentage') {

			return sprintf(__('Discount for %s (%s off)', 'iugu-woocommerce'), esc_attr($this->title), $value);

		} // end if;

		return sprintf(__('Discount for %s', 'iugu-woocommerce'), esc_attr($this->title));

	} // end discount_name;

	/**
	 * Display the discount in the gateway title.
	 *
	 * @param string $title
	 * @param string $id
	 * @return void
	 */
	public function discount_payment_method_title($title, $gateway_id) {

		if (!is_checkout() && ! (defined('DOING_AJAX') && DOING_AJAX)) {

			return $title;

		} // end if;

		if ($gateway_id === $this->id) {

			if ($this->discount_value && 0 < $this->discount_value) {

				if ($this->discount_type && $this->discount_type == 'percentage') {

					$value = $this->discount_value . '%';

				} else {

					$value = wc_price($this->discount_value);

				} // end if;

				$title .= ' <small>(' . sprintf(__('%s off', 'iugu-woocommerce'), $value) . ')</small>';

			} // end if;

			return $title;

		} // end if;

		return $title;

	} // end discount_payment_method_title;

	/**
	 * Undocumented function
	 *
	 * @param WC_Cart $cart
	 * @param mixed $total
	 * @return void
	 */
	public function add_discount($cart, $total) {

		if (is_admin() && !defined('DOING_AJAX') || is_cart()) {

			return;

		} // end if;

		if (WC()->session->chosen_payment_method === $this->id) {

			if ($this->discount_value > 0) {

				$total_with_discount = $this->calculate_discount($this->discount_type, $this->discount_value, $total);

				return $total_with_discount;

			} // end if;

		} // end if;

	} // end add_discount;

} // end WC_Iugu_Bank_Slip_Gateway;
