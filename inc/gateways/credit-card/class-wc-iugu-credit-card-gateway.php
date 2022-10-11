<?php
if (!defined('ABSPATH')) {
	exit;
} // end if;

/**
 * iugu Payment Credit Card Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class   WC_Iugu_Credit_Card_Gateway
 * @extends WC_Iugu_Credit_Card_Woocommerce_Subscription_Gateway
 */
class WC_Iugu_Credit_Card_Gateway extends WC_Iugu_Credit_Card_Woocommerce_Subscription_Gateway {

	const gateway_id = 'iugu-credit-card';
	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                   = WC_Iugu_Credit_Card_Gateway::gateway_id;
		parent::__construct();
		global $woocommerce;
		$this->icon                 = apply_filters('iugu_woocommerce_credit_card_icon', '');
		$this->method_title         = __('iugu - Credit card', IUGU);
		$this->method_description   = __('Accept credit card payments using iugu.', IUGU);
		$this->supports             = array_merge(
			$this->supports,
			array(
				'refunds',
				'tokenization',
				'add_payment_method',
			)
		);
		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();
		// Options.
		$this->pass_interest        = $this->get_option('pass_interest');
		$this->smallest_installment = $this->get_option('smallest_installment', 5);
		/**
		 * Actions.
		 */
		add_action('woocommerce_api_wc_iugu_credit_card_gateway', array($this, 'notification_handler'));
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_payment_token_set_default', array($this, 'payment_token_set_default'), 10, 2);
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
		add_action('woocommerce_email_after_order_table', array($this, 'email_instructions'), 10, 3);
		add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'), 9999);
		add_action('woocommerce_api_iugu_add_new_payment_method', array($this, 'add_new_payment_method'));
		if (is_admin()) {
			add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
		} // end if;
	} // end __construct;

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __('Enable/Disable', IUGU),
				'type'    => 'checkbox',
				'label'   => __('Enable credit card payments with iugu', IUGU),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __('Title', IUGU),
				'type'        => 'text',
				'description' => __('Payment method title seen on the checkout page.', IUGU),
				'default'     => __('Credit card', IUGU)
			),
			'description' => array(
				'title'       => __('Description', IUGU),
				'type'        => 'textarea',
				'description' => __('Payment method description seen on the checkout page.', IUGU),
				'default'     => __('Pay with credit card', IUGU)
			),
			'ignore_due_email' => array(
				'title'            => __('Ignore due email', IUGU),
				'type'              => 'checkbox',
				'label'       => __('When checked, Iugu will not send billing emails to the payer', IUGU),
				'default'           => 'no'
			),
			'payment' => array(
				'title'       => __('Payment options', IUGU),
				'type'        => 'title',
				'description' => ''
			),
			'smallest_installment' => array(
				'title'       => __('Smallest installment value', IUGU),
				'type'        => 'text',
				'description' => __('Smallest value of each installment. Value can\'t be lower than 5.', IUGU),
				'default'     => '5',
			),
			'pass_interest' => array(
				'title'       => __('Uses interest in installments', IUGU),
				'type'        => 'checkbox',
				'label'       => __('Pass on the installments\' interest to the customer.', IUGU),
				'description' => __('This option is only for display and should mimic your iugu account\'s settings.', IUGU),
				'desc_tip'    => true,
				'default'     => 'no'
			),
			'interest_rate_on_installment_1' => array(
				'title'             => __('Interest rate on installment 1', IUGU),
				'type'              => 'number',
				'description'       => __('Enter the interest rate set in your iugu plan.', IUGU) . ' ' .
					__('This option is only for display and should mimic your iugu account\'s settings.', IUGU),
				'desc_tip'          => true,
				'default'           => '2.51',
				'custom_attributes' => array(
					'step' => 'any'
				)
			),
			'interest_rate_on_installment_2' => array(
				'title'             => __('Interest rate on installment 2', IUGU),
				'type'              => 'number',
				'description'       => __('Enter the interest rate set in your iugu plan.', IUGU) . ' ' .
					__('This option is only for display and should mimic your iugu account\'s settings.', IUGU),
				'desc_tip'          => true,
				'default'           => '3.21',
				'custom_attributes' => array(
					'step' => 'any'
				)
			),
			'interest_rate_on_installment_3' => array(
				'title'             => __('Interest rate on installment 3', IUGU),
				'type'              => 'number',
				'description'       => __('Enter the interest rate set in your iugu plan.', IUGU) . ' ' .
					__('This option is only for display and should mimic your iugu account\'s settings.', IUGU),
				'desc_tip'          => true,
				'default'           => '3.21',
				'custom_attributes' => array(
					'step' => 'any'
				)
			),
			'interest_rate_on_installment_4' => array(
				'title'             => __('Interest rate on installment 4', IUGU),
				'type'              => 'number',
				'description'       => __('Enter the interest rate set in your iugu plan.', IUGU) . ' ' .
					__('This option is only for display and should mimic your iugu account\'s settings.', IUGU),
				'desc_tip'          => true,
				'default'           => '3.21',
				'custom_attributes' => array(
					'step' => 'any'
				)
			),
			'interest_rate_on_installment_5' => array(
				'title'             => __('Interest rate on installment 5', IUGU),
				'type'              => 'number',
				'description'       => __('Enter the interest rate set in your iugu plan.', IUGU) . ' ' .
					__('This option is only for display and should mimic your iugu account\'s settings.', IUGU),
				'desc_tip'          => true,
				'default'           => '3.21',
				'custom_attributes' => array(
					'step' => 'any'
				)
			),
			'interest_rate_on_installment_6' => array(
				'title'             => __('Interest rate on installment 6', IUGU),
				'type'              => 'number',
				'description'       => __('Enter the interest rate set in your iugu plan.', IUGU) . ' ' .
					__('This option is only for display and should mimic your iugu account\'s settings.', IUGU),
				'desc_tip'          => true,
				'default'           => '3.21',
				'custom_attributes' => array(
					'step' => 'any'
				)
			),
			'interest_rate_on_installment_7' => array(
				'title'             => __('Interest rate on installment 7', IUGU),
				'type'              => 'number',
				'description'       => __('Enter the interest rate set in your iugu plan.', IUGU) . ' ' .
					__('This option is only for display and should mimic your iugu account\'s settings.', IUGU),
				'desc_tip'          => true,
				'default'           => '3.55',
				'custom_attributes' => array(
					'step' => 'any'
				)
			),
			'interest_rate_on_installment_7' => array(
				'title'             => __('Interest rate on installment 7', IUGU),
				'type'              => 'number',
				'description'       => __('Enter the interest rate set in your iugu plan.', IUGU) . ' ' .
					__('This option is only for display and should mimic your iugu account\'s settings.', IUGU),
				'desc_tip'          => true,
				'default'           => '3.55',
				'custom_attributes' => array(
					'step' => 'any'
				)
			),
			'interest_rate_on_installment_8' => array(
				'title'             => __('Interest rate on installment 8', IUGU),
				'type'              => 'number',
				'description'       => __('Enter the interest rate set in your iugu plan.', IUGU) . ' ' .
					__('This option is only for display and should mimic your iugu account\'s settings.', IUGU),
				'desc_tip'          => true,
				'default'           => '3.55',
				'custom_attributes' => array(
					'step' => 'any'
				)
			),
			'interest_rate_on_installment_9' => array(
				'title'             => __('Interest rate on installment 9', IUGU),
				'type'              => 'number',
				'description'       => __('Enter the interest rate set in your iugu plan.', IUGU) . ' ' .
					__('This option is only for display and should mimic your iugu account\'s settings.', IUGU),
				'desc_tip'          => true,
				'default'           => '3.55',
				'custom_attributes' => array(
					'step' => 'any'
				)
			),
			'interest_rate_on_installment_10' => array(
				'title'             => __('Interest rate on installment 10', IUGU),
				'type'              => 'number',
				'description'       => __('Enter the interest rate set in your iugu plan.', IUGU) . ' ' .
					__('This option is only for display and should mimic your iugu account\'s settings.', IUGU),
				'desc_tip'          => true,
				'default'           => '3.55',
				'custom_attributes' => array(
					'step' => 'any'
				)
			),
			'interest_rate_on_installment_11' => array(
				'title'             => __('Interest rate on installment 11', IUGU),
				'type'              => 'number',
				'description'       => __('Enter the interest rate set in your iugu plan.', IUGU) . ' ' .
					__('This option is only for display and should mimic your iugu account\'s settings.', IUGU),
				'desc_tip'          => true,
				'default'           => '3.55',
				'custom_attributes' => array(
					'step' => 'any'
				)
			),
			'interest_rate_on_installment_12' => array(
				'title'             => __('Interest rate on installment 12', IUGU),
				'type'              => 'number',
				'description'       => __('Enter the interest rate set in your iugu plan.', IUGU) . ' ' .
					__('This option is only for display and should mimic your iugu account\'s settings.', IUGU),
				'desc_tip'          => true,
				'default'           => '3.55',
				'custom_attributes' => array(
					'step' => 'any'
				)
			),
			'behavior' => array(
				'title'       => __('Integration behavior', IUGU),
				'type'        => 'title',
				'description' => ''
			),
			'send_only_total' => array(
				'title'   => __('Send only the order total', IUGU),
				'type'    => 'checkbox',
				'label'   => __('When enabled, the customer only gets the order total, not the list of purchased items.', IUGU),
				'default' => 'no'
			),
		);
	}

	/**
	 * Call plugin scripts in front-end.
	 */
	public function frontend_scripts() {
		if (is_checkout() && 'yes' == $this->enabled) {
			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_style('iugu-woocommerce-credit-card-css', plugins_url('assets/css/credit-card' . $suffix . '.css', WC_IUGU_PLUGIN_FILE));
			wp_enqueue_script('iugu-js', $this->api->get_js_url(), array(), null, true);
			wp_enqueue_script('iugu-woocommerce-credit-card-js', plugins_url('assets/js/credit-card' . $suffix . '.js', WC_IUGU_PLUGIN_FILE), array('jquery', 'wc-credit-card-form'), WC_Iugu::CLIENT_VERSION, true);
			wp_localize_script(
				'iugu-woocommerce-credit-card-js',
				'iugu_wc_credit_card_params',
				array(
					'iugu_account_id'               => $this->settings()->iugu_account_id,
					'is_sandbox'                    => $this->settings()->iugu_sandbox,
					'i18n_number_field'             => __('Card number', IUGU),
					'i18n_verification_value_field' => __('Security code', IUGU),
					'i18n_expiration_field'         => __('Expiry date', IUGU),
					'i18n_first_name_field'         => __('First name', IUGU),
					'i18n_last_name_field'          => __('Last name', IUGU),
					'i18n_installments_field'       => __('Installments', IUGU),
					'i18n_is_invalid'               => __('is invalid', IUGU)
				)
			);
		}
	}

	/**
	 * Get iugu credit card interest rates.
	 *
	 * @return array
	 */
	public function get_interest_rate() {
		$p1 = $this->get_option('interest_rate_on_installment_1', 2.51);
		$p2 = $this->get_option('interest_rate_on_installment_2', 3.21);
		$p3 = $this->get_option('interest_rate_on_installment_3', 3.21);
		$p4 = $this->get_option('interest_rate_on_installment_4', 3.21);
		$p5 = $this->get_option('interest_rate_on_installment_5', 3.21);
		$p6 = $this->get_option('interest_rate_on_installment_6', 3.21);
		$p7 = $this->get_option('interest_rate_on_installment_7', 3.55);
		$p8 = $this->get_option('interest_rate_on_installment_8', 3.55);
		$p9 = $this->get_option('interest_rate_on_installment_9', 3.55);
		$p10 = $this->get_option('interest_rate_on_installment_10', 3.55);
		$p11 = $this->get_option('interest_rate_on_installment_11', 3.55);
		$p12 = $this->get_option('interest_rate_on_installment_12', 3.55);
		$rates = apply_filters('iugu_woocommerce_interest_rates', array(
			'1'  => $p1,
			'2'  => $p2,
			'3'  => $p3,
			'4'  => $p4,
			'5'  => $p5,
			'6'  => $p6,
			'7'  => $p7,
			'8'  => $p8,
			'9'  => $p9,
			'10' => $p10,
			'11' => $p11,
			'12' => $p12,
		));
		return $rates;
	}

	public function get_order_total_local($remove_juros) {
		$total    = 0;
		$order_id = absint(get_query_var('order-pay'));
		// Gets order total from "pay for order" page.
		if (0 < $order_id) {
			$order = wc_get_order($order_id);
			if ($order) {
				$total = (float) $order->get_total();
				if ($remove_juros) {
					foreach ($order->get_fees() as $fee) {
						if ($fee['name'] == __('Fees', IUGU)) {
							$total -= $fee['line_total'];
						}
					}
				}
			}
			// Gets order total from cart/checkout.
		} else if (isset(WC()->cart) && (0 < WC()->cart->total)) {
			$total = (float) WC()->cart->total;
			if ($remove_juros) {
				foreach (WC()->cart->get_fees() as $fee) {
					if ($fee->name == __('Fees', IUGU)) {
						$total -= $fee->amount;
					}
				}
			}
		} else if (isset(WC()->cart) && isset(WC()->cart->recurring_carts)) {
			foreach (WC()->cart->recurring_carts as $cart) {
				$total += $cart->total;
				if ($remove_juros) {
					foreach ($cart->get_fees() as $fee) {
						if ($fee->name == __('Fees', IUGU)) {
							$total -= $fee->amount;
						}
					}
				}
			}
		}
		return $total;
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
		$order_total = $this->get_order_total_local(true);
		$template_params = array();
		if (!is_add_payment_method_page()) {
			$registration_required = false;
			if ($this->existe_subscriptions) {
				if (WC_Subscriptions_Cart::cart_contains_subscription()) {
					if (WC() && WC()->checkout()) {
						$registration_required = WC()->checkout()->is_registration_required();
					}
					if (!$registration_required) {
						if (function_exists('wcs_cart_contains_renewal')) {
							$registration_required = wcs_cart_contains_renewal();
						}
					}	
				}
			}
			$registration_required = apply_filters('iugu-payment-gateway-registration_required', $registration_required);
			wp_enqueue_script('wc-credit-card-form');
			$iugu_card_installments = 0;
			$order_id = absint(get_query_var('order-pay'));
			if ($order_id > 0) {
				$iugu_card_installments = get_post_meta($order_id, 'iugu_card_installments', true);
				if (!$registration_required) {
					if (function_exists('wcs_is_subscription')) {
						$registration_required = wcs_is_subscription($order_id);
					}
				}
			}
			$installments = $this->api->get_max_installments();
			if ($installments < $iugu_card_installments) {
				$installments = $iugu_card_installments;
			}
			if ($installments == 1 && $iugu_card_installments == 0) {
				$iugu_card_installments = 1;
			}
			$template_params = array(
				'fixed_installments'   => $iugu_card_installments,
				'order_total'          => $order_total,
				'installments'         => $installments,
				'smallest_installment' => 5 <= $this->smallest_installment ? $this->smallest_installment : 5,
				'pass_interest'        => $this->pass_interest,
				'rates'                => $this->get_interest_rate(),
				'payment_methods'      => $this->api->get_payment_methods(),
				'registration_required' => $registration_required,
			);
		} else {
			$myaccount_page = get_option('woocommerce_myaccount_page_id');
			$myaccount_page_url = '';
			if ($myaccount_page) {
				$myaccount_page_url = get_permalink($myaccount_page);
			}
			wp_enqueue_script('iugu-js', $this->api->get_js_url(), array(), null, true);
			wp_enqueue_script(
				'iugu-credit-card-mask',
				plugins_url('assets/js/jquery.mask.js', WC_IUGU_PLUGIN_FILE),
				array('jquery'),
				WC_Iugu::CLIENT_VERSION,
				true
			);
			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script(
				'iugu-credit-card-my-account',
				plugins_url('assets/js/my-account-credit-card' . $suffix . '.js', WC_IUGU_PLUGIN_FILE),
				array('jquery', 'iugu-js', 'iugu-credit-card-mask'),
				WC_Iugu::CLIENT_VERSION,
				true
			);
			wp_localize_script(
				'iugu-credit-card-my-account',
				'iugu_wc_credit_card_params',
				array(
					'ajaxurl'         => get_site_url() . '/wc-api/iugu_add_new_payment_method',
					'redirect'        => $myaccount_page_url . 'payment-methods/',
					'iugu_account_id' => $this->settings()->iugu_account_id,
					'is_sandbox'      => $this->settings()->iugu_sandbox,
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
		$order = wc_get_order($order_id);
		$user_id = get_current_user_id();
		$customer_id = $this->api->get_customer_id($order);
		if (!isset($_POST['iugu_save_card'])) {
			$_POST['iugu_save_card'] = 'off';
		}
		if (!isset($_POST['iugu_save_default'])) {
			$_POST['iugu_save_default'] = 'off';
		}
		if (isset($_POST['iugu_card_installments'])) {
			update_post_meta($order->get_id(), 'iugu_card_installments', sanitize_text_field($_POST['iugu_card_installments']));
		}
		/**
		 * Tratamento do salvamento do cartão.
		 */
		if (
			isset($_POST['iugu_token']) &&
			isset($_POST['iugu_save_card']) &&
			$_POST['iugu_save_card'] == 'on'
		) {
			/**
			 * Temos token, e o usuário quer salvar o cartão. Então vamos salvar e colocar seu ID em $_POST, e remover o token, pois ele não pode ser reutilizado.
			 */
			$response = $this->api->create_customer_payment_method($order, $_POST['iugu_token'], $customer_id, $_POST['iugu_save_default'] == 'on');
			if (isset($response['errors']) && $response['errors']) {
				wp_send_json_error(array(
					'error' => __('Unable to add this credit card. Please, try again.', IUGU)
				));
			} else {
				if (isset($response['id'])) {
					$_POST['customer_payment_method_id'] = $response['id'];
					unset($_POST['iugu_token']);
				}
			}
		} // end if;
		/**
		 * Processamento do pagamento.
		 */
		if (!isset($_POST['customer_payment_method_id']) && !isset($_POST['iugu_token'])) {
			$this->settings()->logger->add($this->id, 'Error doing the charge for order ' . $order->get_order_number() . ': Missing the "iugu_token" and "customer_payment_method_id".');
			$this->api->add_error('<strong>' . esc_attr($this->title) . '</strong>: ' . __('Please, make sure your credit card details have been entered correctly and that your browser supports JavaScript.', IUGU));
			return array(
				'result'   => 'fail',
				'redirect' => ''
			);
		} // end if;
		if (function_exists('wcs_is_subscription')) {
			if (wcs_is_subscription($order_id)) {
				$_iugu_customer_payment_method_id = isset($_POST['customer_payment_method_id']) ? sanitize_text_field($_POST['customer_payment_method_id']) : '';
				update_post_meta($order_id, '_iugu_customer_payment_method_id', $_iugu_customer_payment_method_id);
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url($order),
					'success' => true
				);
			}
		}
		$api_return = $this->api->process_payment($order_id);
		return $api_return;
	}

	/**
	 * Thank You page message.
	 *
	 * @param  int    $order_id Order ID.
	 *
	 * @return string
	 */
	public function thankyou_page($order_id) {
		$order = wc_get_order($order_id);
		// WooCommerce 3.0 or later.
		if (is_callable(array($order, 'get_meta'))) {
			$data = $order->get_meta('_iugu_wc_transaction_data');
		} else {
			$data = get_post_meta($order_id, '_iugu_wc_transaction_data', true);
		}
		if (isset($data['installments']) && $order->has_status('processing')) {
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
	public function email_instructions($order, $sent_to_admin, $plain_text = false) {
		// WooCommerce 3.0 or later.
		if (is_callable(array($order, 'get_meta'))) {
			if ($sent_to_admin || !$order->has_status(array('processing', 'on-hold')) || $this->id !== $order->get_payment_method()) {
				return;
			}
			$data = $order->get_meta('_iugu_wc_transaction_data');
		} else {
			if ($sent_to_admin || !$order->has_status(array('processing', 'on-hold')) || $this->id !== $order->get_payment_method()) {
				return;
			}
			$data = get_post_meta($order->get_id(), '_iugu_wc_transaction_data', true);
		}
		if (isset($data['installments'])) {
			if ($plain_text) {
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
		if (
			in_array($hook, array('woocommerce_page_wc-settings', 'woocommerce_page_woocommerce_settings')) &&
			((isset($_GET['section']) && strtolower($this->id) == strtolower($_GET['section'])) || (isset($_GET['section']) && strtolower(get_class($this)) == strtolower($_GET['section'])))
		) {
			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script('iugu-credit-card-admin', plugins_url('assets/js/admin-credit-card' . $suffix . '.js', WC_IUGU_PLUGIN_FILE), array('jquery'), WC_Iugu::CLIENT_VERSION, true);
		} // end if;
	} // end admin_scripts.

	/**
	 * Adds a new payment method and set to the customer.
	 *
	 * @return void.
	 */
	public function add_new_payment_method() {
		$user_id = get_current_user_id();
		$customer_id = $this->api->get_customer_id();
		if ($customer_id && isset($_POST['iugu_card_token'])) {
			$response = $this->api->create_customer_payment_method(NULL, $_POST['iugu_card_token'], $customer_id, false);
			if (isset($response['errors']) && $response['errors']) {
				wp_send_json_error(array(
					'error' => __('Unable to add this credit card. Please, try again.', IUGU)
				));
			} // end if;
			wp_send_json($response);
		} else {
			wc_add_notice(__('Card information not valid.', IUGU), 'error');
			wp_send_json(array(''));
		} // end if;
	} // end add_new_payment_method;

	public function payment_token_set_default($token_id, $token) {
		$user_id = get_current_user_id();
		$customer_id = $this->api->get_customer_id();
		$this->api->set_default_payment_method($customer_id, $token->get_token());
	}
} // end WC_Iugu_Credit_Card_Gateway;
