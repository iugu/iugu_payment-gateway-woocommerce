<?php
if (!defined('ABSPATH')) {
    exit;
} // end if;

class WC_Iugu_Woocommerce_Subscription_Gateway extends WC_Iugu_Gateway {

    public function __construct() {
        parent::__construct();
        $this->existe_subscriptions = class_exists('WC_Subscriptions_Order');
        if ($this->existe_subscriptions) {
            $this->supports             = array_merge(
                $this->supports,
                array(
                    'subscriptions',
                    'subscription_cancellation',
                    'subscription_reactivation',
                    'subscription_suspension',
                    'subscription_amount_changes',
                    'subscription_date_changes',
                    'multiple_subscriptions',
                )
            );
            add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'woocommerce_scheduled_subscription_payment'), 10, 2);
        }
    }

    public function woocommerce_scheduled_subscription_payment($amount_total, $renewal_order) {
        if ('yes' == $this->debug) {
            $this->log->add($this->id, 'Processing a subscription payment for order ' . $renewal_order->get_order_number());
        }
        $this->process_payment($renewal_order->get_id());
    }

}
