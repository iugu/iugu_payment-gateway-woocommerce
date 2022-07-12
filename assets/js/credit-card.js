/* global iugu_wc_credit_card_params, Iugu */
/*jshint devel: true */
(function($) {
    'use strict';

    $(function() {
        var iugu_submit = false;
        /**
         * Process the credit card data when submit the checkout form.
         */
        Iugu.setAccountID(iugu_wc_credit_card_params.iugu_account_id);
        if (iugu_wc_credit_card_params.is_sandbox) {
            Iugu.setTestMode(true);
        }

        $('form.checkout').on('checkout_place_order_iugu-credit-card', function() {
            return formHandler(this);
        });

        $('form.checkout').on('change', 'input[name^="payment_method"]', function() {
            $('body').trigger('update_checkout');
        });

        $('form#order_review').submit(function() {
            return formHandler(this);
        });

        /**
         * Form Handler.
         *
         * @param  {object} form
         *
         * @return {bool}
         */
        function formHandler(form) {
            if (iugu_submit) {
                iugu_submit = false;
                return true;
            }
            $('.iugu-token').remove();
            if (!$('#payment_method_iugu-credit-card').is(':checked')) {
                return true;
            }

            if ($('#iugu-save-card').is(':checked')) {
                var podeSalvar = $('#iugu-is_user_logged_in').val() == '1';
                if (!podeSalvar) {
                    podeSalvar = $('#iugu-is_registration_required').val() == '1';
                }
                if (!podeSalvar) {
                    podeSalvar = $('#iugu-woocommerce_enable_signup_and_login_from_checkout').val() == '1';
                    if (podeSalvar) {
                        podeSalvar = $('#createaccount').is(':checked');
                    }
                }
                if (!podeSalvar) {
                    alert($('#iugu-save-card').attr('placeholder'));
                    return false;
                }
            }
            var $form = $(form);
            var creditCardForm = $('#iugu-credit-card-fields', $form);
            var installments = $('#iugu-card-installments').val();

            if ($('#customer-payment-method-id').length > 0 && $('#customer-payment-method-id').val() !== '') {
                if (installments < 1) {
                    $('.woocommerce-error', creditCardForm).remove();
                    creditCardForm.prepend('<div class="woocommerce-error"><ul><li>' + iugu_wc_credit_card_params.i18n_installments_field + ' ' + iugu_wc_credit_card_params.i18n_is_invalid + '.</li></ul></div>');
                } else {
                    // Usando forma de pagamento salva
                    $('.iugu-token', $form).remove();
                    iugu_submit = true;
                    $form.submit();
                }
            } else {
                // Usando nova forma de pagamento: precisamos criar token com iugu.js
                var cardExpiry = $form.find('#iugu-card-expiry').val().replaceAll(' ', '');
                var errorHtml = '';
                // Seta sem espaços
                $form.find('#iugu-card-expiry').val(cardExpiry);
                Iugu.createPaymentToken(form, function(data) {
                    if (installments < 1) {
                        if (!data.errors) {
                            data.errors = {};
                        }
                        data.errors.installments = 'is_invalid';
                    }
                    if (data.errors) {
                        $('.woocommerce-error', creditCardForm).remove();
                        errorHtml += '<ul>';
                        $.each(data.errors, function(key, value) {
                            var errorMessage = value;
                            if ('is_invalid' === errorMessage) {
                                errorMessage = iugu_wc_credit_card_params.i18n_is_invalid;
                            }
                            errorHtml += '<li>' + iugu_wc_credit_card_params['i18n_' + key + '_field'] + ' ' + errorMessage + '.</li>';
                        });
                        errorHtml += '</ul>';
                        creditCardForm.prepend('<div class="woocommerce-error">' + errorHtml + '</div>');
                    } else {
                        // Remove any old hash input.
                        $('.iugu-token', $form).remove();
                        // Add the hash input.
                        $form.append($('<input class="iugu-token" name="iugu_token" type="hidden" />').val(data.id));
                        // Submit the form.
                        iugu_submit = true;
                        $form.submit();
                    }
                });
            }
            return false;
        }
    });

}(jQuery));