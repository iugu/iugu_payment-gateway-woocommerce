<?php
if (!defined('ABSPATH')) {
	exit;
} // end if;

class WC_Iugu_Credit_Card_Woocommerce_Subscription_Gateway extends WC_Iugu_Woocommerce_Subscription_Gateway {

	public function __construct() {
		parent::__construct();
		if ($this->existe_subscriptions) {
			$this->supports             = array_merge(
				$this->supports,
				array(
					'subscription_payment_method_change',
					'subscription_payment_method_change_customer',
				)
			);
			add_filter('woocommerce_subscription_payment_meta', array($this, 'add_subscription_payment_meta'), 10, 2);
			add_filter('woocommerce_subscription_validate_payment_meta', array($this, 'validate_subscription_payment_meta'), 10, 2);
			add_action('woocommerce_subscription_failing_payment_method_updated_' . $this->id, array($this, 'woocommerce_subscription_failing_payment_method_updated'), 10, 2);
			add_filter('woocommerce_subscription_payment_method_to_display', array($this, 'subscription_payment_method_to_display'), 10, 3);
		}
	}

	public function woocommerce_scheduled_subscription_payment($amount_total, $renewal_order) {
		if ($this->id == 'iugu-credit-card') {
			$iugu_card_installments = get_post_meta($renewal_order->get_id(), 'iugu_card_installments', true);
			if (!isset($iugu_card_installments) || ($iugu_card_installments == '')) {
				$iugu_card_installments = '1';
				update_post_meta($renewal_order->get_id(), 'iugu_card_installments', $iugu_card_installments);
			}
			$_iugu_customer_payment_method_id = get_post_meta($renewal_order->get_id(), '_iugu_customer_payment_method_id', true);
			if (!isset($_iugu_customer_payment_method_id) || ($_iugu_customer_payment_method_id == '')) {
				unset($_iugu_customer_payment_method_id);
				$customer_id = $this->api->get_customer_id($renewal_order);
				if (isset($customer_id)) {
					$iugu_customer = $this->api->get_iugu_customer($customer_id);
					if (isset($iugu_customer)) {
						if (isset($iugu_customer['default_payment_method_id']) && ($iugu_customer['default_payment_method_id'] != '')) {
							$_iugu_customer_payment_method_id = $iugu_customer['default_payment_method_id'];
						}
						if (!isset($_iugu_customer_payment_method_id)) {
							if (isset($iugu_customer['payment_methods'])) {
								$payment_methods = $iugu_customer['payment_methods'];
								if (count($payment_methods) > 0) {
									$_iugu_customer_payment_method_id =	$payment_methods[0]['id'];
								}
							}
						}
						if (isset($_iugu_customer_payment_method_id)) {
							update_post_meta($renewal_order->get_id(), '_iugu_customer_payment_method_id', $_iugu_customer_payment_method_id);
						}
					}
				}
			}
			if (isset($_iugu_customer_payment_method_id)) {
				$_POST['iugu_card_installments'] = $iugu_card_installments;
				$_POST['customer_payment_method_id'] = $_iugu_customer_payment_method_id;
				parent::woocommerce_scheduled_subscription_payment($amount_total, $renewal_order);
			}
		} else {
			parent::woocommerce_scheduled_subscription_payment($amount_total, $renewal_order);
		}
	}

	public function woocommerce_subscription_failing_payment_method_updated($subscription, $renewal_order) {
		update_post_meta($subscription->get_id(), '_iugu_customer_payment_method_id', get_post_meta($renewal_order->id, '_iugu_customer_payment_method_id', true));
		update_post_meta($subscription->get_id(), 'iugu_card_installments', get_post_meta($renewal_order->id, 'iugu_card_installments', true));
	}

	public function add_subscription_payment_meta($payment_meta, $subscription) {
		$payment_meta[$this->id] = array(
			'post_meta' => array(
				'_iugu_customer_payment_method_id' => array(
					'value' => get_post_meta($subscription->get_id(), '_iugu_customer_payment_method_id', true),
					'label' => __('IUGU Payment Method ID', IUGU),
				),
				'iugu_card_installments' => array(
					'value' => get_post_meta($subscription->get_id(), 'iugu_card_installments', true),
					'label' => __('Installments', IUGU),
				),
			),
		);
		return $payment_meta;
	}

	public function validate_subscription_payment_meta($payment_method_id, $payment_meta) {
		if ($this->id === $payment_method_id) {
			if (!isset($payment_meta['post_meta']['_iugu_customer_payment_method_id']['value']) || empty($payment_meta['post_meta']['_iugu_customer_payment_method_id']['value'])) {
				throw new Exception('A "_iugu_customer_payment_method_id" value is required.');
			}
			if (!isset($payment_meta['post_meta']['iugu_card_installments']['value']) || empty($payment_meta['post_meta']['iugu_card_installments']['value'])) {
				throw new Exception('A "iugu_card_installments" value is required.');
			}
		}
	}

	public function subscription_payment_method_to_display($payment_method_to_display, $subscription, $context) {
		if (!is_admin() && get_post_meta($subscription->get_id(), '_payment_method', true) == $this->id) {
			$iugu_card_installments = get_post_meta($subscription->get_id(), 'iugu_card_installments', true);
			if (!isset($iugu_card_installments) || ($iugu_card_installments == '')) {
				$iugu_card_installments = '1';
			}
			$_iugu_customer_payment_method_id = get_post_meta($subscription->get_id(), '_iugu_customer_payment_method_id', true);
			$payment_method = __('Not Found', IUGU);
			if (isset($_iugu_customer_payment_method_id)) {
				$payment_methods = WC_Iugu_API::get_payment_methods();
				if (isset($payment_methods)) {
					foreach ($payment_methods as $pm) {
						if ($pm->get_token() == $_iugu_customer_payment_method_id) {
							$payment_method = $pm->get_card_type() . ' ' . __('end with', IUGU) . ' ' . $pm->get_last4();
							break;
						}
					}
				}
			}
			return $payment_method_to_display . ' ' . $payment_method . ' ' . __('in', IUGU) . ' ' . $iugu_card_installments . 'X';
		} else {
			return $payment_method_to_display;
		}
	}
}
