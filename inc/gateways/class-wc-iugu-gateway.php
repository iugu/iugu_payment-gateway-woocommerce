<?php
if (!defined('ABSPATH')) {
    exit;
} // end if;

class WC_Iugu_Gateway extends WC_Payment_Gateway {

    /**
     * @var WC_IUGU_Settings
     */
    public function settings() {
        return WC_Iugu2::get_instance()->settings();
    }

    public function __construct() {
        $this->has_fields           = true;
        $this->view_transaction_url = 'https://alia.iugu.com/receive/invoices/%s';
        $this->supports             = array(
            'products',
        );
        //'pre-orders',
        $this->title            = $this->get_option('title');
        $this->description      = $this->get_option('description');
        $this->ignore_due_email = $this->get_option('ignore_due_email');
        $this->send_only_total  = $this->get_option('send_only_total', 'no');
        $this->api = new WC_Iugu_API2($this, str_replace('iugu-', '', $this->id));
    }

    public function is_available() {
        $ret = !empty($this->settings()->iugu_account_id) && !empty($this->settings()->iugu_api_token);
        $available = parent::is_available() && $ret && $this->api->using_supported_currency();
        return $available;
    }

    public function process_refund($order_id, $amount = NULL, $reason = '') {
        if ($this->supports('refunds')) {
            return $this->api->refund_order($order_id, $amount);
        }
    }
}
