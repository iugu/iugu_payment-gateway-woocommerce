<?php
if (!defined('ABSPATH')) {
    exit;
}
$applied                                 = !empty($split['applied']) ? $split['applied'] : 'general';
$info                                    = !empty($split['info']) ? $split['info'] : '';
$type                                    = !empty($split['type']) ? $split['type'] : 'percent';
$recipient_account_id                    = !empty($split['recipient_account_id']) ? $split['recipient_account_id'] : '';
$percent                                 = !empty($split['percent']) ? $split['percent'] : '0.00';
$pix_percent                             = !empty($split['pix_percent']) ? $split['pix_percent'] : '0.00';
$bank_slip_percent                       = !empty($split['bank_slip_percent']) ? $split['bank_slip_percent'] : '0.00';
$credit_card_percent                     = !empty($split['credit_card_percent']) ? $split['credit_card_percent'] : '0.00';
$credit_card_1x_percent                  = !empty($split['credit_card_1x_percent']) ? $split['credit_card_1x_percent'] : '0.00';
$credit_card_2x_percent                  = !empty($split['credit_card_2x_percent']) ? $split['credit_card_2x_percent'] : '0.00';
$credit_card_3x_percent                  = !empty($split['credit_card_3x_percent']) ? $split['credit_card_3x_percent'] : '0.00';
$credit_card_4x_percent                  = !empty($split['credit_card_4x_percent']) ? $split['credit_card_4x_percent'] : '0.00';
$credit_card_5x_percent                  = !empty($split['credit_card_5x_percent']) ? $split['credit_card_5x_percent'] : '0.00';
$credit_card_6x_percent                  = !empty($split['credit_card_6x_percent']) ? $split['credit_card_6x_percent'] : '0.00';
$credit_card_7x_percent                  = !empty($split['credit_card_7x_percent']) ? $split['credit_card_7x_percent'] : '0.00';
$credit_card_8x_percent                  = !empty($split['credit_card_8x_percent']) ? $split['credit_card_8x_percent'] : '0.00';
$credit_card_9x_percent                  = !empty($split['credit_card_9x_percent']) ? $split['credit_card_9x_percent'] : '0.00';
$credit_card_10x_percent                 = !empty($split['credit_card_10x_percent']) ? $split['credit_card_10x_percent'] : '0.00';
$credit_card_11x_percent                 = !empty($split['credit_card_11x_percent']) ? $split['credit_card_11x_percent'] : '0.00';
$credit_card_12x_percent                 = !empty($split['credit_card_12x_percent']) ? $split['credit_card_12x_percent'] : '0.00';
$cents                                   = !empty($split['cents']) ? $split['cents'] : '0.00';
$pix_cents                               = !empty($split['pix_cents']) ? $split['pix_cents'] : '0.00';
$bank_slip_cents                         = !empty($split['bank_slip_cents']) ? $split['bank_slip_cents'] : '0.00';
$credit_card_cents                       = !empty($split['credit_card_cents']) ? $split['credit_card_cents'] : '0.00';
$credit_card_1x_cents                    = !empty($split['credit_card_1x_cents']) ? $split['credit_card_1x_cents'] : '0.00';
$credit_card_2x_cents                    = !empty($split['credit_card_2x_cents']) ? $split['credit_card_2x_cents'] : '0.00';
$credit_card_3x_cents                    = !empty($split['credit_card_3x_cents']) ? $split['credit_card_3x_cents'] : '0.00';
$credit_card_4x_cents                    = !empty($split['credit_card_4x_cents']) ? $split['credit_card_4x_cents'] : '0.00';
$credit_card_5x_cents                    = !empty($split['credit_card_5x_cents']) ? $split['credit_card_5x_cents'] : '0.00';
$credit_card_6x_cents                    = !empty($split['credit_card_6x_cents']) ? $split['credit_card_6x_cents'] : '0.00';
$credit_card_7x_cents                    = !empty($split['credit_card_7x_cents']) ? $split['credit_card_7x_cents'] : '0.00';
$credit_card_8x_cents                    = !empty($split['credit_card_8x_cents']) ? $split['credit_card_8x_cents'] : '0.00';
$credit_card_9x_cents                    = !empty($split['credit_card_9x_cents']) ? $split['credit_card_9x_cents'] : '0.00';
$credit_card_10x_cents                   = !empty($split['credit_card_10x_cents']) ? $split['credit_card_10x_cents'] : '0.00';
$credit_card_11x_cents                   = !empty($split['credit_card_11x_cents']) ? $split['credit_card_11x_cents'] : '0.00';
$credit_card_12x_cents                   = !empty($split['credit_card_12x_cents']) ? $split['credit_card_12x_cents'] : '0.00';

// permit_aggregated Permite agregar comissionamento percentual + fixo.

$hide_percent                 = $type == 'cents' ? ' hide' : '';
$hide_cents                   = $type == 'percent' ? ' hide' : '';
$hide_applied_general         = '';
$hide_applied_payment_method  = '';
$hide_applied_payment_method2 = '';
$hide_applied_installments    = '';
if ($applied == 'general') {
    $hide_applied_payment_method  = ' hide';
    $hide_applied_payment_method2 = ' hide';
    $hide_applied_installments    = ' hide';
} else if ($applied == 'payment-method') {
    $hide_applied_general         = ' hide';
    $hide_applied_installments    = ' hide';    
} else if ($applied == 'installments') {
    $hide_applied_general         = ' hide';
    $hide_applied_payment_method2 = ' hide';
}
?>

<div class="wc-iugu-split options_group" style="margin: 1em; border: 2px solid #e5e5e5;">
    <input type="hidden" name="product_split_position[<?php echo esc_attr($loop); ?>]" class="wc-iugu-split-position" value="<?php echo esc_attr($loop); ?>" />

    <?php
    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-info-[' . $loop . ']',
        'name'          => 'product_iugu_info[' . $loop . ']',
        'class'         => 'short',
        'label'         => __('Information', IUGU),
        'type'          => 'text',
        'value'         => esc_attr($info),
        'description'   => __('Text to help identify the account id.', IUGU),
        'desc_tip'      => true,
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-recipient_account_id-[' . $loop . ']',
        'name'          => 'product_iugu_recipient_account_id[' . $loop . ']',
        'class'         => 'short',
        'label'         => __('Account ID', IUGU),
        'type'          => 'text',
        'value'         => esc_attr($recipient_account_id),
        'description'   => __('Account ID that will receive the split.', IUGU),
        'desc_tip'      => true,
    ));

    woocommerce_wp_select(
        array(
            'id'            => 'wc-iugu-split-applied-[' . $loop . ']',
            'name'          => 'product_iugu_applied[' . $loop . ']',
            'class'         => 'select short product_iugu_applied',
            'label'         => __('Applied', IUGU),
            'options'       => array(
                'general'        => __('General', IUGU),
                'payment-method' => __('Payment Method', IUGU),
                'installments'   => __('Installments', IUGU),
            ),
            'value'         => esc_attr($applied),
            'desc_tip'      => true,
        )
    );

    woocommerce_wp_select(
        array(
            'id'            => 'wc-iugu-split-type-[' . $loop . ']',
            'name'          => 'product_iugu_type[' . $loop . ']',
            'class'         => 'select short product_iugu_type',
            'label'         => __('Split Type', IUGU),
            'options'       => array(
                'both'        => __('Both', IUGU),
                'percent'     => __('Percent', IUGU),
                'cents'       => __('Fixed', IUGU),
            ),
            'value'         => esc_attr($type),
            'description'   => __('Both -> Allows you to add percentage + fixed commission.', IUGU),
            'desc_tip'      => true,
        )
    );

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-percent-[' . $loop . ']',
        'name'          => 'product_iugu_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-general-percent' . $hide_percent . $hide_applied_general,
        'label'         => __('Percent', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-pix-percent-[' . $loop . ']',
        'name'          => 'product_iugu_pix_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-payment-method-percent' . $hide_percent . $hide_applied_payment_method,
        'label'         => __('Percent PIX', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($pix_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-bank-slip-percent-[' . $loop . ']',
        'name'          => 'product_iugu_bank_slip_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-payment-method-percent' . $hide_percent . $hide_applied_payment_method,
        'label'         => __('Percent Bank Slip', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($bank_slip_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-percent-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-payment-method2-percent' . $hide_percent . $hide_applied_payment_method2,
        'label'         => __('Percent Credit Card', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-1x-percent-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_1x_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-installments-percent' . $hide_percent . $hide_applied_installments,
        'label'         => __('Percent Credit Card 1x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_1x_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-2x-percent-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_2x_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-installments-percent' . $hide_percent . $hide_applied_installments,
        'label'         => __('Percent Credit Card 2x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_2x_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-3x-percent-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_3x_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-installments-percent' . $hide_percent . $hide_applied_installments,
        'label'         => __('Percent Credit Card 3x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_3x_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-4x-percent-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_4x_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-installments-percent' . $hide_percent . $hide_applied_installments,
        'label'         => __('Percent Credit Card 4x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_4x_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-5x-percent-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_5x_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-installments-percent' . $hide_percent . $hide_applied_installments,
        'label'         => __('Percent Credit Card 5x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_5x_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-6x-percent-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_6x_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-installments-percent' . $hide_percent . $hide_applied_installments,
        'label'         => __('Percent Credit Card 6x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_6x_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-7x-percent-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_7x_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-installments-percent' . $hide_percent . $hide_applied_installments,
        'label'         => __('Percent Credit Card 7x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_7x_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-8x-percent-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_8x_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-installments-percent' . $hide_percent . $hide_applied_installments,
        'label'         => __('Percent Credit Card 8x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_8x_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-9x-percent-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_9x_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-installments-percent' . $hide_percent . $hide_applied_installments,
        'label'         => __('Percent Credit Card 9x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_9x_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-10x-percent-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_10x_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-installments-percent' . $hide_percent . $hide_applied_installments,
        'label'         => __('Percent Credit Card 10x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_10x_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-11x-percent-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_11x_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-installments-percent' . $hide_percent . $hide_applied_installments,
        'label'         => __('Percent Credit Card 11x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_11x_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-12x-percent-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_12x_percent[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-percent split-installments-percent' . $hide_percent . $hide_applied_installments,
        'label'         => __('Percent Credit Card 12x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_12x_percent),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-cents-[' . $loop . ']',
        'name'          => 'product_iugu_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-general-cents' . $hide_cents . $hide_applied_general,
        'label'         => __('Fixed', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-pix-cents-[' . $loop . ']',
        'name'          => 'product_iugu_pix_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-payment-method-cents' . $hide_cents . $hide_applied_payment_method,
        'label'         => __('Fixed PIX', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($pix_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-bank-slip-cents-[' . $loop . ']',
        'name'          => 'product_iugu_bank_slip_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-payment-method-cents' . $hide_cents . $hide_applied_payment_method,
        'label'         => __('Fixed Bank Slip', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($bank_slip_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-cents-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-payment-method2-cents' . $hide_cents . $hide_applied_payment_method2,
        'label'         => __('Fixed Credit Card', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));


    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-1x-cents-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_1x_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-installments-cents' . $hide_cents . $hide_applied_installments,
        'label'         => __('Fixed Credit Card 1x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_1x_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-2x-cents-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_2x_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-installments-cents' . $hide_cents . $hide_applied_installments,
        'label'         => __('Fixed Credit Card 2x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_2x_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-3x-cents-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_3x_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-installments-cents' . $hide_cents . $hide_applied_installments,
        'label'         => __('Fixed Credit Card 3x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_3x_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-4x-cents-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_4x_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-installments-cents' . $hide_cents . $hide_applied_installments,
        'label'         => __('Fixed Credit Card 4x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_4x_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-5x-cents-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_5x_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-installments-cents' . $hide_cents . $hide_applied_installments,
        'label'         => __('Fixed Credit Card 5x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_5x_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-6x-cents-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_6x_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-installments-cents' . $hide_cents . $hide_applied_installments,
        'label'         => __('Fixed Credit Card 6x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_6x_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-7x-cents-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_7x_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-installments-cents' . $hide_cents . $hide_applied_installments,
        'label'         => __('Fixed Credit Card 7x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_7x_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-8x-cents-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_8x_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-installments-cents' . $hide_cents . $hide_applied_installments,
        'label'         => __('Fixed Credit Card 8x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_8x_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-9x-cents-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_9x_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-installments-cents' . $hide_cents . $hide_applied_installments,
        'label'         => __('Fixed Credit Card 9x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_9x_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-10x-cents-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_10x_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-installments-cents' . $hide_cents . $hide_applied_installments,
        'label'         => __('Fixed Credit Card 10x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_10x_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-11x-cents-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_11x_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-installments-cents' . $hide_cents . $hide_applied_installments,
        'label'         => __('Fixed Credit Card 11x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_11x_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    woocommerce_wp_text_input(array(
        'id'            => 'wc-iugu-split-credit-card-12x-cents-[' . $loop . ']',
        'name'          => 'product_iugu_credit_card_12x_cents[' . $loop . ']',
        'class'         => 'short',
        'wrapper_class' => 'wc-iugu-split-fieldtype-cents split-installments-cents' . $hide_cents . $hide_applied_installments,
        'label'         => __('Fixed Credit Card 12x', IUGU),
        'type'          => 'number',
        'value'         => esc_attr($credit_card_12x_cents),
        'custom_attributes' => array(
            'step'     => 'any',
            'min'    => '0'
        ),
    ));

    ?>

    <p>
        <button type="button" class="wc-iugu-remove-split button"><?php esc_html_e('Remove', IUGU); ?></button>
    </p>

</div>