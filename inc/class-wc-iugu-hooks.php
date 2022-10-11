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
	 * @var WC_Iugu_API
	 */
	protected $api;

	/**
	 * Initialize my account actions.
	 */
	public function __construct() {
		$this->api = new WC_Iugu_API();
		add_action('wp_ajax_wc_iugu_notification_handler', array($this, 'webhook_notification_handler'));
		add_action('wp_ajax_nopriv_wc_iugu_notification_handler', array($this, 'webhook_notification_handler'));
		if (class_exists('WC_Subscriptions_Order')) {
			add_filter('woocommerce_subscription_settings', array($this, 'add_woocommerce_subscriptions_settings'), 10, 1);
		}
		add_filter('woocommerce_available_payment_gateways', array($this, 'filter_gateways_based_on_product'), 10);

		add_action('woocommerce_payment_token_deleted', array($this, 'remove_payment_method'), 10, 2);
		add_action('woocommerce_cart_calculate_fees', array($this, 'add_amount_to_order'), 20, 1);
		add_action('woocommerce_cancelled_order', array($this, 'cancelled_order'), 10, 1);
		add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'my_custom_checkout_field_display_admin_order_meta'), 10, 1);
		if (class_exists('WC_Subscriptions_Order')) {
			add_filter('woocommerce_subscriptions_is_recurring_fee', array($this, 'subscriptions_is_recurring_fee'), 10, 3);
			add_filter('woocommerce_subscriptions_can_user_renew_early_via_modal', array($this, 'subscriptions_can_user_renew_early_via_modal'), 10, 3);
			add_filter('woocommerce_subscriptions_can_user_renew_early', array($this, 'subscriptions_can_user_renew_early'), 10, 4);
			add_filter('woocommerce_subscription_can_date_be_updated', array($this, 'woocommerce_subscription_can_date_be_updated'), 10, 3);
		}
	} // end __construct;

	public function woocommerce_subscription_can_date_be_updated($can_date_be_updated, $date_type, $subscription) {
		if (($date_type == 'cancelled') || ($date_type == 'end') || ($date_type == 'next_payment')) {
			$can_date_be_updated = $can_date_be_updated || $subscription->get_status() == 'pending-cancel';
		}
		return $can_date_be_updated;
	}

	function subscriptions_is_recurring_fee($is_recurring, $fee, $cart) {
		if ($fee->name == __('Bank Slip discount', IUGU)) {
			$iugu_subscription_discount_type = get_option('iugu_subscription_discount_type');
			$is_recurring = $iugu_subscription_discount_type === 'persistent';
		} else {
			$is_recurring = true;
		}
		return $is_recurring;
	}

	function subscriptions_can_user_renew_early($can_renew_early, $subscription, $user_id, $reason) {
		if (!$can_renew_early) {
			$can_renew_early = $reason == 'subscription_still_in_free_trial';
		}
		return $can_renew_early;
	}

	public function subscriptions_can_user_renew_early_via_modal($subscription_is_current_user, $subscription, $user_id) {
		if ($subscription->get_payment_method() == 'iugu-credit-card') {
			return true;
		} else {
			return false;
		}
	}

	public function cancelled_order($order_id) {
		$this->api->cancel_invoice($order_id);
	}

	function my_custom_checkout_field_display_admin_order_meta($order) {
		echo '<p class="form-field"><strong>' . __('IUGU Customer ID', IUGU) . ':</strong> ' . get_user_meta($order->get_user_id(), '_iugu_customer_id', true) . '</p>';
	}

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
					'name' => __('Iugu Subscriptions', IUGU),
					'type' => 'title',
					'id'   => 'iugu_handle_subscriptions',
				),
				array(
					'name'     	=> __('Bank Slip discount type', IUGU),
					'desc'     	=> __('The subscription using the bank slip has what kind of discount. Persistent, ie every month has the same discount, or only on the first payment?', IUGU),
					'id'       	=> 'iugu_subscription_discount_type',
					'default'  	=> 'persistent',
					'type'     	=> 'select',
					'options'   => array(
						'persistent'  => __('Persistent', IUGU),
						'one_time'    => __('One Time', IUGU)
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
	 * Deletes a payment method when the user clicks in the Delete button.
	 *
	 * @param string $token_id The token ID.
	 * @param object $token    The Token object.
	 * @return void
	 */
	public function remove_payment_method($token_id, $token) {
		$user_id = get_current_user_id();
		$customer_id = $this->api->get_customer_id();
		$this->api->delete_customer_payment_method($customer_id, $token->get_token());
	} // end remove_payment_method;	

	/**
	 * Filter gateways based on the payable with option.
	 *
	 * @param array $gateways
	 * @return array.
	 */
	public function filter_gateways_based_on_product($gateways) {
		if (is_account_page()) {
			return $gateways;
		}
		$order_id = absint(get_query_var('order-pay'));
		$change_payment = 0;
		if (class_exists('WC_Subscriptions_Order')) {
			$change_payment = isset($_GET['change_payment_method']) ? wc_clean($_GET['change_payment_method']) : 0;
			if ($order_id == 0 && $change_payment > 0) {
				$order_id = $change_payment;
			}
		}
		if ($order_id > 0) {
			$filtered_gateways = array();
			$_payment_method = get_post_meta($order_id, '_payment_method', true);
			if ($_payment_method == 'iugu-pix' || $_payment_method == 'pre_orders_pay_later') {
				if (isset($gateways['iugu-pix'])) {
					$filtered_gateways['iugu-pix'] = $gateways['iugu-pix'];
				}
			}
			if ($_payment_method == 'iugu-bank-slip' || $_payment_method == 'pre_orders_pay_later') {
				if (isset($gateways['iugu-bank-slip'])) {
					$filtered_gateways['iugu-bank-slip'] = $gateways['iugu-bank-slip'];
				}
			}
			if ($_payment_method == 'iugu-credit-card' || $_payment_method == 'pre_orders_pay_later') {
				$filtered_gateways['iugu-credit-card'] = $gateways['iugu-credit-card'];
				if ($change_payment > 0) {
					$renew_url = '';
					if (function_exists('wcs_get_early_renewal_url')) {
						$renew_url = wcs_get_early_renewal_url($order_id);
						if ($renew_url != '') {
							$renew_url = '<b><a style="padding-left: 1em;" href="' . $renew_url . '">' . __('Click here to do this now.', IUGU) . '</a></b>';
						}
					}
					$msg = sprintf(__('Use the "%s" option to change payment method.', IUGU), __('Renew now', IUGU)) . $renew_url;
					if (!wc_has_notice($msg, 'notice')) {
						wc_add_notice($msg, 'notice');
					}
				}
			}
			if ($change_payment > 0 && count($filtered_gateways) == 0) {
				$renew_url = '';
				if (function_exists('wcs_get_early_renewal_url')) {
					$renew_url = wcs_get_early_renewal_url($order_id);
					if ($renew_url != '') {
						$renew_url = '<b><a style="padding-left: 1em;" href="' . $renew_url . '">' . __('Click here to do this now.', IUGU) . '</a></b>';
					}
				}
				$msg = sprintf(__('Use the "%s" option for this signature.', IUGU), __('Renew now', IUGU)) . $renew_url;
				if (!wc_has_notice($msg, 'notice')) {
					wc_add_notice($msg, 'notice');
				}
				$msg = __('ATTENTION - When changing the payment method, a new charge will be made.', IUGU);
				if (!wc_has_notice($msg, 'notice')) {
					wc_add_notice($msg, 'notice');
				}
			}
			return $filtered_gateways;
		}
		if (WC()->cart) {
			$filtered_gateways = array();
			foreach (WC()->cart->get_cart() as $cart_item) {
				$product_id = $cart_item['product_id'];
				$payable_with = get_post_meta($product_id, '_iugu_payable_with', true);
				if ($payable_with == null || $payable_with == '') {
					$payable_with = 'all';
				}
				$filtered_gateways[$payable_with] = $payable_with;
			} // end if;
			if (isset($filtered_gateways['all'])) {
				return $gateways;
			} // end if;
			foreach ($filtered_gateways as $filter_gateway_key) {
				if (isset($gateways[$filter_gateway_key])) {
					$filtered_gateways[$filter_gateway_key] = $gateways[$filter_gateway_key];
				}
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
		if (is_admin() && !defined('DOING_AJAX')) {
			return;
		}
		if (isset($_POST['post_data'])) {
			parse_str(sanitize_text_field($_POST['post_data']), $post_data);
		} else {
			$post_data = $_POST;
		}
		$cart_fees = $cart->get_fees();
		$clear_fees = array();
		foreach ($cart_fees as $item_id => $fee) {
			if (
				$fee->name != __('Fees', IUGU) &&
				$fee->name != __('Bank Slip discount', IUGU)
			) {
				$clear_fees[$item_id] = $fee;
			}
		}
		$cart->fees_api()->set_fees($clear_fees);
		$chosen_payment_method = WC()->session->chosen_payment_method;
		if ($chosen_payment_method === 'iugu-credit-card') {
			$iugu_options = get_option('woocommerce_iugu-credit-card_settings');
			$iugu_card_installments = '1';
			if (isset($post_data['iugu_card_installments'])) {
				$iugu_card_installments = $post_data['iugu_card_installments'];
			}
			$tmp = 'interest_rate_on_installment_' . $iugu_card_installments;
			if (
				isset($iugu_options[$tmp]) &&
				isset($iugu_options['pass_interest']) &&
				($iugu_options['pass_interest'] == 'yes')
			) {
				$interest_rate_on_installment = $iugu_options[$tmp];
				$subtotal = $cart->get_subtotal() -
					$cart->get_discount_total() +
					$cart->get_shipping_total();
				foreach ($cart->get_taxes() as $tax) {
					$subtotal += $tax;
				}
				foreach ($cart->get_fees() as $fee) {
					$subtotal += $fee->amount;
				}
				$fee_value = (($interest_rate_on_installment / 100) * $subtotal);
				if ($fee_value > 0) {
					$cart->add_fee(__('Fees', IUGU), $fee_value);
				}
			}
		}
		if (WC()->session->chosen_payment_method === 'iugu-bank-slip') {
			$check_desconto_boleto = true;
			if (class_exists('WC_Subscriptions_Order') && isset($cart->next_payment_date)) {
				$iugu_subscription_discount_type = get_option('iugu_subscription_discount_type');
				$check_desconto_boleto = $iugu_subscription_discount_type === 'persistent';
			}
			if ($check_desconto_boleto) {
				$iugu_options = get_option('woocommerce_iugu-bank-slip_settings');
				if (isset($iugu_options['enable_discount']) and $iugu_options['enable_discount'] == 'yes') {
					$type = $iugu_options['discount_type'];
					$discount_value = $iugu_options['discount_value'];
					if ($type == 'percentage') {
						$subtotal = $cart->get_subtotal() -
							$cart->get_discount_total();
						$value = ($subtotal / 100) * $discount_value;
					} else {
						$discount_value = $discount_value;
						$value = $discount_value;
					} // end if;
					if ($value) {
						$cart->add_fee(__('Bank Slip discount', IUGU), -$value);
					} // end if ;
				} // end if;
			}
		} // end if;
	} // end add_amount_to_order;

	/**
	 * Create iugu webhook for subscriptions that are handled by Iugu.
	 *
	 * @return void
	 */
	public function create_iugu_webhook() {
		$id_webhook = get_option('_wc_iugu_webhook_created');
		$webhook = $this->api->get_iugu_webhook($id_webhook);
		if (!($webhook && isset($webhook['id']))) {
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
