<?php
if (!defined('ABSPATH')) {
    exit;
} // end if;

class WC_Iugu_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->has_fields           = true;
        $this->view_transaction_url = 'https://alia.iugu.com/receive/invoices/%s';
        $this->supports             = array(
            'products',
        );
        //'pre-orders',
        $this->title            = $this->get_option('title');
        $this->description      = $this->get_option('description');
        $this->account_id       = $this->get_option('account_id');
        $this->api_token        = $this->get_option('api_token');
        $this->ignore_due_email = $this->get_option('ignore_due_email');
        $this->send_only_total  = $this->get_option('send_only_total', 'no');
        $this->sandbox          = $this->get_option('sandbox', 'no');
        $this->debug            = $this->get_option('debug');
        $this->api = new WC_Iugu_API2($this, str_replace('iugu-', '', $this->id));
        if ('yes' == $this->debug) {
            if (class_exists('WC_Logger')) {
                $this->log = new WC_Logger();
            } else {
                $this->log = $woocommerce->logger();
            }
        }
    }

    public function is_available() {
        $ret = !empty($this->account_id) && !empty($this->api_token);
        $available = parent::is_available() && $ret && $this->api->using_supported_currency();
        return $available;
    }

    public function process_refund($order_id, $amount = NULL, $reason = '') {
        if ($this->supports('refunds')) {
            return $this->api->refund_order($order_id, $amount);
        }
    }
}
