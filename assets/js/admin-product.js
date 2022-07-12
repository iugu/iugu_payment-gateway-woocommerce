jQuery(function($) {
    'use strict';

    var wc_init_admin_product = {
        getSplitField: function() {
            var data = {
                security: wc_iugu_params.nonce.get_split_field,
                action: 'wc_iugu_get_split_field',
            };

            return $.ajax({
                type: 'POST',
                data: data,
                url: wc_iugu_params.ajax_url
            });
        },
        checkHideShowCentsPercent: function(_this) {
            parent = $(_this).parents('.wc-iugu-split');
            var type = $(parent).find('.product_iugu_type').val();
            var applied = $(parent).find('.product_iugu_applied').val();
            parent.find('.wc-iugu-split-fieldtype-cents').removeClass('show').addClass('hide');
            parent.find('.wc-iugu-split-fieldtype-percent').removeClass('show').addClass('hide');
            if (type !== 'both') {
                parent.find('.split-' + applied + '-' + type).removeClass('hide').addClass('show');
            } else {
                parent.find('.split-' + applied + '-cents').removeClass('hide').addClass('show');
                parent.find('.split-' + applied + '-percent').removeClass('hide').addClass('show');
            }
            if (applied == 'payment-method') {
                if (type !== 'both') {
                    parent.find('.split-payment-method2-' + type).removeClass('hide').addClass('show');
                } else {
                    parent.find('.split-payment-method2-cents').removeClass('hide').addClass('show');
                    parent.find('.split-payment-method2-percent').removeClass('hide').addClass('show');
                }
            } else if (applied == 'installments') {
                if (type !== 'both') {
                    parent.find('.split-payment-method-' + type).removeClass('hide').addClass('show');
                } else {
                    parent.find('.split-payment-method-cents').removeClass('hide').addClass('show');
                    parent.find('.split-payment-method-percent').removeClass('hide').addClass('show');
                }
            }
        },
        init: function() {
            $('#iugu_woocommerce_options')
                .on('click', '.wc-iugu-add-split', function() {
                    var loop = $('.wc-iugu-splits .wc-iugu-split').length;
                    $.when(wc_init_admin_product.getSplitField()).then(function(html) {
                        var html = html.html;
                        html = html.replace(/{loop}/g, loop);
                        $('.wc-iugu-splits').append(html);
                    });
                    return false;
                })
                .on('click', '.wc-iugu-remove-split', function() {
                    var answer = confirm(wc_iugu_params.i18n.confirm_remove_split);
                    if (answer) {
                        var split = $(this).closest('.wc-iugu-split');
                        $(split).find('input').val('');
                        $(split).remove();
                        $('.wc-iugu-splits .wc-iugu-split').each(function(index, el) {
                            var this_index = index;
                            $(this).find('.product_split_position').val(this_index);
                            $(this).find('select, input, textarea').prop('name', function(i, val) {
                                var field_name = val.replace(/\[[0-9]+\]/g, '[' + this_index + ']');
                                return field_name;
                            });
                        });
                    }
                    return false;
                })
                .on('change', '.product_iugu_type', function() {
                    wc_init_admin_product.checkHideShowCentsPercent(this);
                })
                .on('change', '.iugu_payable_with', function() {
                    var selectedValue = $(this).val();
                    parent = $(this).parents('.wc-iugu-payment-options');
                    switch (selectedValue) {
                        case 'all':
                            parent.find('.iugu_number_installments').removeClass('hide').addClass('show');
                            break;
                        case 'iugu-credit-card':
                            parent.find('.iugu_number_installments').removeClass('hide').addClass('show');
                            break;
                        default:
                            parent.find('.iugu_number_installments').removeClass('show').addClass('hide');
                            break;
                    }
                })
                .on('change', '.product_iugu_applied', function() {
                    wc_init_admin_product.checkHideShowCentsPercent(this);
                })
        }
    };

    wc_init_admin_product.init();
});