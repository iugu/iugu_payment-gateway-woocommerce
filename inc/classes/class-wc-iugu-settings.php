<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_IUGU_Settings extends WC_Settings_API {

    /**
     * @var IUGULogger
     **/
    public $logger;

    function __construct() {
        $this->id = 'general';
        $this->plugin_id = IUGU;
        $this->init_settings();
        $this->init_form_fields();
        $this->iugu_account_id        = $this->get_option('iugu_account_id');
        $this->iugu_api_token         = trim($this->get_option('iugu_api_token'));
        $this->iugu_status_pending    = $this->get_option('iugu_status_pending');
        $this->iugu_status_processing = $this->get_option('iugu_status_processing');
        $this->iugu_sandbox           = $this->get_option('iugu_sandbox') == 'yes' ? true : false;
        $this->iugu_debug             = $this->get_option('iugu_debug') == 'yes' ? true : false;
        $this->iugu_customer_id_start_date_validation = $this->get_option('iugu_customer_id_start_date_validation');
        $this->logger = new IUGULogger(IUGU, $this->iugu_debug);
        if (is_admin()) {
            add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50);
            add_action('woocommerce_settings_tabs_' . IUGU, array(&$this, 'admin_options'));
            add_action('woocommerce_update_options_' . IUGU, array(&$this, 'process_admin_options'), 10);
            add_action('woocommerce_update_options_' . IUGU, array(&$this, 'process_admin_options_local'), 100);
        }
    }

    public function process_admin_options_local() {
        WC_Iugu::get_instance()->iugu_hooks->create_iugu_webhook();
    }

    public static function add_settings_tab($settings_tabs) {
        $settings_tabs[IUGU] = __('IUGU', IUGU);
        return $settings_tabs;
    }

    private function old_iugu_options_tipo($tipo) {
        $iugu_options = get_option('woocommerce_iugu-' . $tipo . '_settings');
        if (is_array($iugu_options) && 
            isset($iugu_options['enabled']) && 
            ($iugu_options['enabled'] == 'yes') && 
            isset($iugu_options['api_token'])) {
            return $iugu_options;
        }
        else {
            return null;
        }
    }

    private function old_iugu_options() {
        $iugu_options = $this->old_iugu_options_tipo('credit-card');
        if ($iugu_options != null) {
            return $iugu_options;
        }
        $iugu_options = $this->old_iugu_options_tipo('bank-slip');
        if ($iugu_options != null) {
            return $iugu_options;
        }
        $iugu_options = $this->old_iugu_options_tipo('pix');
        if ($iugu_options != null) {
            return $iugu_options;
        }
    }

    private function check_value_old_iugu_options($field, $old_field_name, $default) {
        if ($field['default'] == 'iugu_' . $old_field_name) {
            $field['default'] = $default;
            $old_iugu_options = $this->old_iugu_options();
            if (is_array($old_iugu_options) && isset($old_iugu_options[$old_field_name])) {
                $field['default'] = $old_iugu_options[$old_field_name];
            }
        }
        return $field;
    }

    protected function set_defaults($field) {
        if (isset($field['default'])) {
            $field = $this->check_value_old_iugu_options($field, 'account_id', '');
            $field = $this->check_value_old_iugu_options($field, 'api_token', '');
            $field = $this->check_value_old_iugu_options($field, 'sandbox', 'no');
            $field = $this->check_value_old_iugu_options($field, 'debug', 'no');
        }
        if (!isset($field['default'])) {
            $field['default'] = '';
        }
        return $field;
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'iugu_integration' => array(
                'title'       => __('Integration settings', IUGU),
                'type'        => 'title',
                'description' => ''
            ),
            'iugu_account_id' => array(
                'title'             => __('Account ID', IUGU),
                'type'              => 'text',
                'description'       => sprintf(__('Your iugu account\'s unique ID, found in %s.', IUGU), '<a href="https://app.iugu.com/account" target="_blank">' . __('iugu account settings', IUGU) . '</a>'),
                'default'           => 'iugu_account_id',
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'iugu_api_token' => array(
                'title'            => __('API Token', IUGU),
                'type'              => 'text',
                'description'       => sprintf(__('For real payments, use a LIVE API token. When iugu sandbox is enabled, use a TEST API token. API tokens can be found/created in %s.', IUGU), '<a href="https://app.iugu.com/account" target="_blank">' . __('iugu account settings', IUGU) . '</a>'),
                'default'           => 'iugu_api_token',
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'iugu_general_settings' => array(
                'title'       => __('General Settings', IUGU),
                'type'        => 'title',
                'description' => ''
            ),
            'iugu_status_pending' => array(
				'title'             => __('Status for Pending Payment', IUGU),
				'type'              => 'select',
				'description'       => '',
				'default'           => 'wc-pending',
				'options' => wc_get_order_statuses(),
			),
            'iugu_status_processing' => array(
				'title'             => __('Status for Payment Confirmation', IUGU),
				'type'              => 'select',
				'description'       => '',
				'default'           => 'wc-processing',
				'options' => wc_get_order_statuses(),
			),
            'iugu_customer_id_start_date_validation' => array(
                'title'       => __('Customer ID Validation Start Date', IUGU),
                'type'        => 'date',
            ),
            'iugu_testing' => array(
                'title'       => __('Gateway testing', IUGU),
                'type'        => 'title',
                'description' => ''
            ),
            'iugu_sandbox' => array(
                'title'       => __('iugu sandbox', IUGU),
                'type'        => 'checkbox',
                'label'       => __('Enable iugu sandbox', IUGU),
                'default'     => 'iugu_sandbox',
                'description' => sprintf(__('Used to test payments. Don\'t forget to use a TEST API token, which can be found/created in %s.', IUGU), '<a href="https://iugu.com/settings/account" target="_blank">' . __('iugu account settings', IUGU) . '</a>')
            ),
            'iugu_debug' => array(
                'title'       => __('Debugging', IUGU),
                'type'        => 'checkbox',
                'label'       => __('Enable logging', IUGU),
                'default'     => 'iugu_debug',
                'description' => sprintf(__('Log iugu events, such as API requests, for debugging purposes. The log can be found in %s.', IUGU), WC_Iugu::get_log_view($this->id))
            )
        );
    }

}
