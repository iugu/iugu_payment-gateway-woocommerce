<?php
if (!defined( 'ABSPATH')) {

	exit;

} // end if;

/**
 * iugu Payment Credit Card Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class   WC_Iugu_Credit_Card_Gateway
 * @extends WC_Payment_Gateway
 */
class WC_Iugu_Credit_Card_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {

		global $woocommerce;

		$this->id                   = 'iugu-credit-card';
		$this->icon                 = apply_filters( 'iugu_woocommerce_credit_card_icon', '' );
		$this->method_title         = __( 'iugu - Credit card', 'iugu-woocommerce' );
		$this->method_description   = __( 'Accept credit card payments using iugu.', 'iugu-woocommerce' );
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
			'multiple_subscriptions',
			'refunds',
			'pre-orders',
			'tokenization',
			'add_payment_method',
		);

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Options.
		$this->title                = $this->get_option('title');
		$this->description          = $this->get_option('description');
		$this->account_id           = $this->get_option('account_id');
		$this->api_token            = $this->get_option('api_token');
		$this->ignore_due_email     = $this->get_option('ignore_due_email');
		$this->pass_interest        = $this->get_option('pass_interest');
		$this->smallest_installment = $this->get_option('smallest_installment', 5);
		$this->transaction_rate     = $this->get_option('transaction_rate', 7);
		$this->send_only_total      = $this->get_option('send_only_total', 'no');
		$this->sandbox              = $this->get_option('sandbox', 'no');
		$this->debug                = $this->get_option('debug');

		// Active logs.
		if ('yes' == $this->debug) {

			if (class_exists( 'WC_Logger')) {

				$this->log = new WC_Logger();

			} else {

				$this->log = $woocommerce->logger();

			} // end if;

		} // end if;

		$this->api = new WC_Iugu_API($this, 'credit-card');

		/**
		 * Actions.
		 */
		add_action('woocommerce_api_wc_iugu_credit_card_gateway', array($this, 'notification_handler'));

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

		add_action('woocommerce_email_after_order_table', array($this, 'email_instructions'), 10, 3);

		add_action('wp_enqueue_scripts', array($this, 'frontend_scripts' ), 9999);

		add_action('woocommerce_api_iugu_add_new_payment_method', array($this, 'add_new_payment_method'));

    add_action('woocommerce_payment_token_deleted', array($this, 'remove_payment_method'), 10, 2);

		if (is_admin()) {

			add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

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

		$available = parent::is_available() && $api && $this->api->using_supported_currency();

		return $available;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'iugu-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable credit card payments with iugu', 'iugu-woocommerce' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'iugu-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Payment method title seen on the checkout page.', 'iugu-woocommerce' ),
				'default'     => __( 'Credit card', 'iugu-woocommerce' )
			),
			'description' => array(
				'title'       => __( 'Description', 'iugu-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description seen on the checkout page.', 'iugu-woocommerce' ),
				'default'     => __( 'Pay with credit card', 'iugu-woocommerce' )
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
			'smallest_installment' => array(
				'title'       => __( 'Smallest installment value', 'iugu-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Smallest value of each installment. Value can\'t be lower than 5.', 'iugu-woocommerce' ),
				'default'     => '5',
			),
			'pass_interest' => array(
				'title'       => __( 'Pass on interest', 'iugu-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Pass on the installments\' interest to the customer.', 'iugu-woocommerce' ),
				'description' => __( 'This option is only for display and should mimic your iugu account\'s settings.', 'iugu-woocommerce' ),
				'desc_tip'    => true,
				'default'     => 'no'
			),
			'transaction_rate' => array(
				'title'             => __( 'Transaction rate', 'iugu-woocommerce' ),
				'type'              => 'number',
				'description'       => __( 'Enter the transaction rate set up in your iugu plan.', 'iugu-woocommerce' ) . ' ' . __( 'This option is only for display and should mimic your iugu account\'s settings.', 'iugu-woocommerce' ),
				'desc_tip'          => true,
				'default'           => '7',
				'custom_attributes' => array(
					'step' => 'any'
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
	}

	/**
	 * Call plugin scripts in front-end.
	 */
	public function frontend_scripts() {
		if ( is_checkout() && 'yes' == $this->enabled ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_style( 'iugu-woocommerce-credit-card-css', plugins_url( 'assets/css/credit-card' . $suffix . '.css', WC_IUGU_PLUGIN_FILE ) );

			wp_enqueue_script( 'iugu-js', $this->api->get_js_url(), array(), null, true );
			wp_enqueue_script( 'iugu-woocommerce-credit-card-js', plugins_url( 'assets/js/credit-card' . $suffix . '.js', WC_IUGU_PLUGIN_FILE ), array( 'jquery', 'wc-credit-card-form' ), WC_Iugu::CLIENT_VERSION, true );

			wp_localize_script(
				'iugu-woocommerce-credit-card-js',
				'iugu_wc_credit_card_params',
				array(
					'account_id'                    => $this->account_id,
					'is_sandbox'                    => $this->sandbox,
					'i18n_number_field'             => __( 'Card number', 'iugu-woocommerce' ),
					'i18n_verification_value_field' => __( 'Security code', 'iugu-woocommerce' ),
					'i18n_expiration_field'         => __( 'Expiry date', 'iugu-woocommerce' ),
					'i18n_first_name_field'         => __( 'First name', 'iugu-woocommerce' ),
					'i18n_last_name_field'          => __( 'Last name', 'iugu-woocommerce' ),
					'i18n_installments_field'       => __( 'Installments', 'iugu-woocommerce' ),
					'i18n_is_invalid'               => __( 'is invalid', 'iugu-woocommerce' )
				)
			);
		}
	}

	/**
	 * Credit Card payment fields.
	 *
	 * @return void
	 */
	public function payment_fields() {

		if ($description = $this->get_description()) {

			echo wpautop(wptexturize($description));

		} // end if;

		/**
		 * Get order total.
		 */
		if (method_exists($this, 'get_order_total')) {

			$order_total = $this->get_order_total();

		} else {

			$order_total = $this->api->get_order_total();

		} // end if;

		$template_params = array();

		if (!is_add_payment_method_page()) {

			wp_enqueue_script('wc-credit-card-form');

			$template_params = array(
				'order_total'          => $order_total,
				'installments'         => $this->api->get_max_installments(),
				'smallest_installment' => 5 <= $this->smallest_installment ? $this->smallest_installment : 5,
				'free_interest'        => 'yes' == $this->pass_interest ? intval( $this->free_interest ) : 12,
				'transaction_rate'     => $this->api->get_transaction_rate(),
				'rates'                => $this->api->get_interest_rate(),
				'payment_methods'      => $this->api->get_payment_methods(get_user_meta(get_current_user_id(), '_iugu_customer_id',true)),
				'default_method'       => $this->api->get_customer_payment_method_id()
			);

		} else {

			wp_enqueue_script('iugu-js', $this->api->get_js_url(), array(), null, true);

			wp_enqueue_script('iugu-credit-card-mask', plugins_url('assets/js/jquery.mask.js', WC_IUGU_PLUGIN_FILE ), array('jquery'), WC_Iugu::CLIENT_VERSION, true);

			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script('iugu-credit-card-my-account', plugins_url('assets/js/my-account-credit-card' . $suffix . '.js', WC_IUGU_PLUGIN_FILE ), array('jquery', 'iugu-js', 'iugu-credit-card-mask'), WC_Iugu::CLIENT_VERSION, true);

			wp_localize_script(
				'iugu-credit-card-my-account',
				'iugu_wc_credit_card_params',
				array(
					'ajaxurl'         => get_site_url() . '/wc-api/iugu_add_new_payment_method',
          'redirect'        => get_site_url() . '/my-account/payment-methods/',
					'account_id'      => $this->account_id,
					'is_sandbox'      => $this->sandbox,
					'payment_methods' => $this->api->get_payment_methods(get_user_meta( get_current_user_id(), '_iugu_customer_id', true )),
					'default_method'  => $this->api->get_customer_payment_method_id(),
					'masks'           => array(
						'iugu-card-number' => '0000 0000 0000 0000',
						'iugu-card-expiry' => '00/0000',
						'iugu-card-cvc'    => '0000'
					)
				)
			);

		} // end if;

		wc_get_template(
			'credit-card/payment-form.php',
			$template_params,
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

		$order = wc_get_order( $order_id );

		/**
		 * Tratamento do salvamento do cartão.
		 */
		if(isset($_POST['iugu_token']) && isset($_POST['iugu_save_card']) && $_POST['iugu_save_card'] == 'on') {

		/**
		 * Temos token, e o usuário quer salvar o cartão. Então vamos salvar e colocar seu ID em $_POST, e remover o token, pois ele não pode ser reutilizado.
		 */
			$_POST['customer_payment_method_id'] = $this->api->create_customer_payment_method($order, $_POST['iugu_token']);

			unset($_POST['iugu_token']);

		} // end if;

		/**
		 * Processamento do pagamento.
		 */
		if (!isset($_POST['customer_payment_method_id']) && !isset($_POST['iugu_token'])) {

			if ( 'yes' === $this->debug ) {

				$this->log->add($this->id, 'Error doing the charge for order ' . $order->get_order_number() . ': Missing the "iugu_token" and "customer_payment_method_id".');

			} // end if;

			$this->api->add_error('<strong>' . esc_attr( $this->title ) . '</strong>: ' . __( 'Please, make sure your credit card details have been entered correctly and that your browser supports JavaScript.', 'iugu-woocommerce'));

			return array(
				'result'   => 'fail',
				'redirect' => ''
			);

		} // end if;

		$api_return = $this->api->process_payment($order_id);

		if ($api_return['success'] == true) {

			$this->api->set_default_payment_method($order, $_POST['customer_payment_method_id']);

		} // end if;

		else if(isset( $_POST['iugu_save_card']) && $_POST['iugu_save_card'] == 'on')
			$this->api->remove_payment_method($order, $_POST['customer_payment_method_id']);

		return $api_return;
	}

	/**
	 * Thank You page message.
	 *
	 * @param  int    $order_id Order ID.
	 *
	 * @return string
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );

		// WooCommerce 3.0 or later.
		if ( is_callable( array( $order, 'get_meta' ) ) ) {
			$data = $order->get_meta( '_iugu_wc_transaction_data' );
		} else {
			$data = get_post_meta( $order_id, '_iugu_wc_transaction_data', true );
		}

		if ( isset( $data['installments'] ) && $order->has_status( 'processing' ) ) {
			wc_get_template(
				'credit-card/payment-instructions.php',
				array(
					'installments' => $data['installments']
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
		// WooCommerce 3.0 or later.
		if ( is_callable( array( $order, 'get_meta' ) ) ) {
			if ( $sent_to_admin || ! $order->has_status( array( 'processing', 'on-hold' ) ) || $this->id !== $order->get_payment_method() ) {
				return;
			}

			$data = $order->get_meta( '_iugu_wc_transaction_data' );
		} else {
			if ( $sent_to_admin || ! $order->has_status( array( 'processing', 'on-hold' ) ) || $this->id !== $order->get_payment_method() ) {
				return;
			}

			$data = get_post_meta( $order->get_id(), '_iugu_wc_transaction_data', true );
		}

		if ( isset( $data['installments'] ) ) {
			if ( $plain_text ) {
				wc_get_template(
					'credit-card/emails/plain-instructions.php',
					array(
						'installments' => $data['installments']
					),
					'woocommerce/iugu/',
					WC_Iugu::get_templates_path()
				);
			} else {
				wc_get_template(
					'credit-card/emails/html-instructions.php',
					array(
						'installments' => $data['installments']
					),
					'woocommerce/iugu/',
					WC_Iugu::get_templates_path()
				);
			}
		}
	}

	/**
	 * Notification handler.
	 */
	public function notification_handler() {
		$this->api->notification_handler();
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Page slug.
	 * @return void.
	 */
	public function admin_scripts($hook) {

		if (in_array($hook, array('woocommerce_page_wc-settings', 'woocommerce_page_woocommerce_settings')) && (isset($_GET['section']) && strtolower(get_class($this)) == strtolower($_GET['section']))) {

			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script('iugu-credit-card-admin', plugins_url('assets/js/admin-credit-card' . $suffix . '.js', WC_IUGU_PLUGIN_FILE ), array( 'jquery' ), WC_Iugu::CLIENT_VERSION, true);

		} // end if;

	} // end admin_scripts.

	/**
	 * Process a refund;
	 *
	 * @param string $order_id WooCommerce Order ID.
	 * @param int    $amount Amount to refund.
	 * @param string $reason Reason to refund.
	 * @return void
	 */
	public function process_refund($order_id, $amount = NULL, $reason = '')	{

		return $this->api->refund_order($order_id, $amount);

	} // end process_refund;

	/**
	 * Adds a new payment method and set to the customer.
	 *
	 * @return void.
	 */
	public function add_new_payment_method() {

		$user_id = get_current_user_id();

		$customer_id = get_user_meta($user_id, '_iugu_customer_id', true);

		if ($customer_id && isset($_POST['iugu_card_token'])) {

			$response = $this->api->create_customer_payment_method('', $_POST['iugu_card_token'], $customer_id);

			if (isset($response['errors']) && $response['errors']) {

				wp_send_json_error(array(
					'error' => __('Unable to add this credit card. Please, try again.', 'iugu-woocommerce')
				));

			} // end if;

			wp_send_json($response);

		} else {

      wc_add_notice(__('Card information not valid.', 'iugu-woocommerce'), 'error');

      wp_send_json(array(''));

		} // end if;

	} // end add_new_payment_method;

} // end WC_Iugu_Credit_Card_Gateway;
