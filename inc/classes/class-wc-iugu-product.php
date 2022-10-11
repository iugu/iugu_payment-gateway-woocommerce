<?php
class WC_IUGU_Product {

    public static function init() {
        add_filter('woocommerce_product_data_tabs', __CLASS__ . '::add_product_tab');
        add_action('woocommerce_product_data_panels', __CLASS__ . '::iugu_woocommerce_options_product_tab_content');
        add_action('wp_ajax_wc_iugu_get_split_field', __CLASS__ . '::ajax_get_split_field');
        add_action('woocommerce_process_product_meta', __CLASS__ . '::process_meta_box', 1);
    }

    protected static function get_posted_product_splits() {
        $product_splits = array();

        if (isset($_POST['product_iugu_type'])) {
            $product_iugu_info                      = $_POST['product_iugu_info'];
            $product_iugu_recipient_account_id      = $_POST['product_iugu_recipient_account_id'];
            $product_iugu_applied                   = $_POST['product_iugu_applied'];
            $product_iugu_type                      = $_POST['product_iugu_type'];
            $product_iugu_percent                   = $_POST['product_iugu_percent'];
            $product_iugu_pix_percent               = $_POST['product_iugu_pix_percent'];
            $product_iugu_bank_slip_percent         = $_POST['product_iugu_bank_slip_percent'];
            $product_iugu_credit_card_percent       = $_POST['product_iugu_credit_card_percent'];
            $product_iugu_credit_card_1x_percent    = $_POST['product_iugu_credit_card_1x_percent'];
            $product_iugu_credit_card_2x_percent    = $_POST['product_iugu_credit_card_2x_percent'];
            $product_iugu_credit_card_3x_percent    = $_POST['product_iugu_credit_card_3x_percent'];
            $product_iugu_credit_card_4x_percent    = $_POST['product_iugu_credit_card_4x_percent'];
            $product_iugu_credit_card_5x_percent    = $_POST['product_iugu_credit_card_5x_percent'];
            $product_iugu_credit_card_6x_percent    = $_POST['product_iugu_credit_card_6x_percent'];
            $product_iugu_credit_card_7x_percent    = $_POST['product_iugu_credit_card_7x_percent'];
            $product_iugu_credit_card_8x_percent    = $_POST['product_iugu_credit_card_8x_percent'];
            $product_iugu_credit_card_9x_percent    = $_POST['product_iugu_credit_card_9x_percent'];
            $product_iugu_credit_card_10x_percent   = $_POST['product_iugu_credit_card_10x_percent'];
            $product_iugu_credit_card_11x_percent   = $_POST['product_iugu_credit_card_11x_percent'];
            $product_iugu_credit_card_12x_percent   = $_POST['product_iugu_credit_card_12x_percent'];
            $product_iugu_cents                     = $_POST['product_iugu_cents'];
            $product_iugu_pix_cents                 = $_POST['product_iugu_pix_cents'];
            $product_iugu_bank_slip_cents           = $_POST['product_iugu_bank_slip_cents'];
            $product_iugu_credit_card_cents         = $_POST['product_iugu_credit_card_cents'];
            $product_iugu_credit_card_1x_cents      = $_POST['product_iugu_credit_card_1x_cents'];
            $product_iugu_credit_card_2x_cents      = $_POST['product_iugu_credit_card_2x_cents'];
            $product_iugu_credit_card_3x_cents      = $_POST['product_iugu_credit_card_3x_cents'];
            $product_iugu_credit_card_4x_cents      = $_POST['product_iugu_credit_card_4x_cents'];
            $product_iugu_credit_card_5x_cents      = $_POST['product_iugu_credit_card_5x_cents'];
            $product_iugu_credit_card_6x_cents      = $_POST['product_iugu_credit_card_6x_cents'];
            $product_iugu_credit_card_7x_cents      = $_POST['product_iugu_credit_card_7x_cents'];
            $product_iugu_credit_card_8x_cents      = $_POST['product_iugu_credit_card_8x_cents'];
            $product_iugu_credit_card_9x_cents      = $_POST['product_iugu_credit_card_9x_cents'];
            $product_iugu_credit_card_10x_cents     = $_POST['product_iugu_credit_card_10x_cents'];
            $product_iugu_credit_card_11x_cents     = $_POST['product_iugu_credit_card_11x_cents'];
            $product_iugu_credit_card_12x_cents     = $_POST['product_iugu_credit_card_12x_cents'];

            for ($i = 0; $i < count($product_iugu_type); $i++) {
                if (!isset($product_iugu_type[$i]) || ('' == $product_iugu_type[$i])) {
                    continue;
                }
                $data                               = array();
                $data['info']                       = sanitize_text_field(wp_unslash($product_iugu_info[$i]));
                $data['recipient_account_id']       = sanitize_text_field(wp_unslash($product_iugu_recipient_account_id[$i]));
                $data['applied']                    = sanitize_text_field(wp_unslash($product_iugu_applied[$i]));
                $data['type']                       = sanitize_text_field(wp_unslash($product_iugu_type[$i]));
                $data['percent']                    = sanitize_text_field(wp_unslash($product_iugu_percent[$i]));
                $data['pix_percent']                = sanitize_text_field(wp_unslash($product_iugu_pix_percent[$i]));
                $data['bank_slip_percent']          = sanitize_text_field(wp_unslash($product_iugu_bank_slip_percent[$i]));
                $data['credit_card_percent']        = sanitize_text_field(wp_unslash($product_iugu_credit_card_percent[$i]));
                $data['credit_card_1x_percent']     = sanitize_text_field(wp_unslash($product_iugu_credit_card_1x_percent[$i]));
                $data['credit_card_2x_percent']     = sanitize_text_field(wp_unslash($product_iugu_credit_card_2x_percent[$i]));
                $data['credit_card_3x_percent']     = sanitize_text_field(wp_unslash($product_iugu_credit_card_3x_percent[$i]));
                $data['credit_card_4x_percent']     = sanitize_text_field(wp_unslash($product_iugu_credit_card_4x_percent[$i]));
                $data['credit_card_5x_percent']     = sanitize_text_field(wp_unslash($product_iugu_credit_card_5x_percent[$i]));
                $data['credit_card_6x_percent']     = sanitize_text_field(wp_unslash($product_iugu_credit_card_6x_percent[$i]));
                $data['credit_card_7x_percent']     = sanitize_text_field(wp_unslash($product_iugu_credit_card_7x_percent[$i]));
                $data['credit_card_8x_percent']     = sanitize_text_field(wp_unslash($product_iugu_credit_card_8x_percent[$i]));
                $data['credit_card_9x_percent']     = sanitize_text_field(wp_unslash($product_iugu_credit_card_9x_percent[$i]));
                $data['credit_card_10x_percent']    = sanitize_text_field(wp_unslash($product_iugu_credit_card_10x_percent[$i]));
                $data['credit_card_11x_percent']    = sanitize_text_field(wp_unslash($product_iugu_credit_card_11x_percent[$i]));
                $data['credit_card_12x_percent']    = sanitize_text_field(wp_unslash($product_iugu_credit_card_12x_percent[$i]));                
                $data['cents']                      = sanitize_text_field(wp_unslash($product_iugu_cents[$i]));
                $data['pix_cents']                  = sanitize_text_field(wp_unslash($product_iugu_pix_cents[$i]));
                $data['bank_slip_cents']            = sanitize_text_field(wp_unslash($product_iugu_bank_slip_cents[$i]));
                $data['credit_card_cents']          = sanitize_text_field(wp_unslash($product_iugu_credit_card_cents[$i]));
                $data['credit_card_1x_cents']       = sanitize_text_field(wp_unslash($product_iugu_credit_card_1x_cents[$i]));
                $data['credit_card_2x_cents']       = sanitize_text_field(wp_unslash($product_iugu_credit_card_2x_cents[$i]));
                $data['credit_card_3x_cents']       = sanitize_text_field(wp_unslash($product_iugu_credit_card_3x_cents[$i]));
                $data['credit_card_4x_cents']       = sanitize_text_field(wp_unslash($product_iugu_credit_card_4x_cents[$i]));
                $data['credit_card_5x_cents']       = sanitize_text_field(wp_unslash($product_iugu_credit_card_5x_cents[$i]));
                $data['credit_card_6x_cents']       = sanitize_text_field(wp_unslash($product_iugu_credit_card_6x_cents[$i]));
                $data['credit_card_7x_cents']       = sanitize_text_field(wp_unslash($product_iugu_credit_card_7x_cents[$i]));
                $data['credit_card_8x_cents']       = sanitize_text_field(wp_unslash($product_iugu_credit_card_8x_cents[$i]));
                $data['credit_card_9x_cents']       = sanitize_text_field(wp_unslash($product_iugu_credit_card_9x_cents[$i]));
                $data['credit_card_10x_cents']      = sanitize_text_field(wp_unslash($product_iugu_credit_card_10x_cents[$i]));
                $data['credit_card_11x_cents']      = sanitize_text_field(wp_unslash($product_iugu_credit_card_11x_cents[$i]));
                $data['credit_card_12x_cents']      = sanitize_text_field(wp_unslash($product_iugu_credit_card_12x_cents[$i]));
                if ($data['type'] == 'percent' || $data['applied'] !== 'general') {
                    $data['cents']                    = '0.00';
                }
                if ($data['type'] == 'percent' || $data['applied'] == 'general') {
                    $data['pix_cents']                = '0.00';
                    $data['bank_slip_cents']          = '0.00';
                    $data['credit_card_cents']        = '0.00';
                }
                if ($data['type'] == 'percent' || $data['applied'] !== 'installments') {
                    $data['credit_card_1x_cents']     = '0.00';
                    $data['credit_card_2x_cents']     = '0.00';
                    $data['credit_card_3x_cents']     = '0.00';
                    $data['credit_card_4x_cents']     = '0.00';
                    $data['credit_card_5x_cents']     = '0.00';
                    $data['credit_card_6x_cents']     = '0.00';
                    $data['credit_card_7x_cents']     = '0.00';
                    $data['credit_card_8x_cents']     = '0.00';
                    $data['credit_card_9x_cents']     = '0.00';
                    $data['credit_card_10x_cents']    = '0.00';
                    $data['credit_card_11x_cents']    = '0.00';
                    $data['credit_card_12x_cents']    = '0.00';
                }
                if ($data['type'] == 'cents' || $data['applied'] !== 'general') {
                    $data['percent']                    = '0.00';
                }
                if ($data['type'] == 'cents' || $data['applied'] == 'general') {
                    $data['pix_percent']                = '0.00';
                    $data['bank_slip_percent']          = '0.00';
                    $data['credit_card_percent']        = '0.00';
                }
                if ($data['type'] == 'cents' || $data['applied'] !== 'installments') {
                    $data['credit_card_1x_percent']     = '0.00';
                    $data['credit_card_2x_percent']     = '0.00';
                    $data['credit_card_3x_percent']     = '0.00';
                    $data['credit_card_4x_percent']     = '0.00';
                    $data['credit_card_5x_percent']     = '0.00';
                    $data['credit_card_6x_percent']     = '0.00';
                    $data['credit_card_7x_percent']     = '0.00';
                    $data['credit_card_8x_percent']     = '0.00';
                    $data['credit_card_9x_percent']     = '0.00';
                    $data['credit_card_10x_percent']    = '0.00';
                    $data['credit_card_11x_percent']    = '0.00';
                    $data['credit_card_12x_percent']    = '0.00';                    
                }
                $product_splits[] = $data;
            }
        }
        return $product_splits;
    }

    public static function process_meta_box($post_id) {
        $product_splits = WC_IUGU_Product::get_posted_product_splits();
        $product = wc_get_product($post_id);
        $product->update_meta_data('_product_iugu_splits', $product_splits);
        $product->update_meta_data('_iugu_payable_with', $_POST['_iugu_payable_with']);
        if ($_POST['_iugu_payable_with'] == 'all' || $_POST['_iugu_payable_with'] == 'iugu-credit-card') {
            $product->update_meta_data('_iugu_number_installments', $_POST['_iugu_number_installments']);
        } else {
            $product->update_meta_data('_iugu_number_installments', '1');
        }
        $product->save();
    }

    public static function ajax_get_split_field($product) {
        check_ajax_referer('wc-iugu-get-split-field', 'security');
        ob_start();
        $split                       = array();
        $loop = "{loop}";
        include(WC_Iugu::get_templates_path() . 'admin/split.php');
        $html = ob_get_clean();
        $html = str_replace(array("\n", "\r"), '', str_replace("'", '"', $html));
        wp_send_json(array('html' => $html));
    }

    public static function iugu_woocommerce_options_product_tab_content() {
        global $post;
        $product        = wc_get_product($post);
        echo '<div id="iugu_woocommerce_options" class="panel woocommerce_options_panel">';
        echo '  <style>';
        echo '    .show {display: block;}';
        echo '    .hide {display: none;}';
        echo '  </style>';
        echo '  <h4 style="padding: 1rem; background: #f6f6f6; margin: 0; border: 1px solid #eee; border-left: none; border-right: none;">' .  __('Payment Options', IUGU) . '</h4>';
        WC_IUGU_Product::display_group_payment_options($product);
        echo '  <h4 style="padding: 1rem; background: #f6f6f6; margin: 0; border: 1px solid #eee; border-left: none; border-right: none;">' .  __('Splits', IUGU) . '</h4>';
        echo '  <div class="wc-iugu-splits">';
        WC_IUGU_Product::display_group_splits($product);
        echo '  </div>';

        echo '  <div class="options_group" style="margin: 1em; border: 2px solid #e5e5e5;">';
        echo '    <p>';
        echo '      <button type="button" class="button wc-iugu-add-split">' .  __('Add new Split', IUGU) . '</button>';
        echo '    </p>';
        echo '  </div>';
        echo '</div>';
    }

    public static function display_group_splits($product) {
        $splits = array_filter((array) $product->get_meta('_product_iugu_splits'));
        $loop = 0;
        foreach ($splits as $split) {
            include(WC_Iugu::get_templates_path() . 'admin/split.php');
            $loop++;
        }
        $params = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => array(
                'get_split_field'   => wp_create_nonce('wc-iugu-get-split-field'),
            ),
            'i18n'     => array(
                'confirm_remove_split'      => __('Are you sure you want remove this split?', IUGU),
            ),
        );

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        wp_register_script('woocommerce_product_iugu', plugins_url('assets/js/admin-product' . $suffix . '.js', WC_IUGU_PLUGIN_FILE), array(), WC_Iugu::CLIENT_VERSION, true);
        wp_localize_script('woocommerce_product_iugu', 'wc_iugu_params', apply_filters('wc_iugu_params', $params));
        wp_enqueue_script('woocommerce_product_iugu');
    }

    public static function display_group_payment_options($product) {
        $_iugu_payable_with = $product->get_meta('_iugu_payable_with');
        $hide_installments = 'hide';
        if ($_iugu_payable_with == 'all' || $_iugu_payable_with == 'iugu-credit-card') {
            $hide_installments = 'show';
        }
        echo '<div class="wc-iugu-payment-options options_group">';
        woocommerce_wp_select(
            array(
                'id'            => '_iugu_payable_with',
                'class'         => 'wc_input_subscription_length select short iugu_payable_with',
                'label'         => __('Avaiable Iugu Payments', IUGU),
                'options'       => array(
                    'all'                => __('All', IUGU),
                    'iugu-bank-slip'     => __('Bank Slip', IUGU),
                    'iugu-credit-card'   => __('Credit Card', IUGU),
                    'iugu-pix'           => __('PIX', IUGU)
                ),
            )
        );
        woocommerce_wp_select(
            array(
                'id'            => '_iugu_number_installments',
                'class'         => 'wc_input_subscription_length select short',
                'wrapper_class' => 'iugu_number_installments ' . $hide_installments,
                'label'         => __('Number of Installments', IUGU),
                'options'       => array(
                    '01'                => '01',
                    '02'                => '02',
                    '03'                => '03',
                    '04'                => '04',
                    '05'                => '05',
                    '06'                => '06',
                    '07'                => '07',
                    '08'                => '08',
                    '09'                => '09',
                    '10'                => '10',
                    '11'                => '11',
                    '12'                => '12'
                ),
            )
        );
        echo '</div>';
    }

    public static function add_product_tab($tabs) {
        $tabs['iugu_woocommerce'] = [
            'label'     => __('IUGU', IUGU),
            'target'    => 'iugu_woocommerce_options',
        ];
        return $tabs;
    }
}
