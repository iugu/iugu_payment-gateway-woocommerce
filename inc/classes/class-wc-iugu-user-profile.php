<?php
class WC_IUGU_UserProfile {

    public static function init() {
        add_filter('woocommerce_customer_meta_fields', __CLASS__ . '::add_woocommerce_customer_meta_fields', 100);
       // add_action( 'show_user_profile', array( $this, 'add_customer_meta_fields' ) );
        //add_action( 'edit_user_profile', array( $this, 'add_customer_meta_fields' ) );

       // add_action( 'personal_options_update', array( $this, 'save_customer_meta_fields' ) );
        //add_action( 'edit_user_profile_update', array( $this, 'save_customer_meta_fields' ) );
    }

    public static function add_woocommerce_customer_meta_fields($fields) {
        $fields['iugu'] = array(
            'title'  => 'IUGU',
            'fields' => array(
                '_iugu_customer_id' => array(
                    'label'       => __('IUGU Customer ID', IUGU),
                    'description' => '',
                ),
                '_iugu_customer_id_date_validation' => array(
                    'label'       => __('Customer ID Validation Date', IUGU),
                    'description' => '',
                )
            )
        );
        return $fields;
    }    
    
}