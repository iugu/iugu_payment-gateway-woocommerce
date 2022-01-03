/*jshint devel: true */
(function($) {
    'use strict';
    $(function() {
        formMasks();
        $('#place_order').on('click', function(e) {
            e.preventDefault();
            /**
             * Process the credit card data when submit the checkout form.
             */
            Iugu.setAccountID(iugu_wc_credit_card_params.account_id);
            if ('yes' === iugu_wc_credit_card_params.is_sandbox) {
                Iugu.setTestMode(true);
            } // end if;
            const card_number = $('#iugu-card-number').val().replaceAll(/ /g, '');
            const card_name = $('#iugu-card-holder-name').val().split(' ', 2);
            const card_expiry = $('#iugu-card-expiry').val().replaceAll(' ', '');
            const card_cvv = $('#iugu-card-cvc').val();
            const card_object = Iugu.CreditCard(
                card_number,
                card_expiry.substring(0, 2),
                card_expiry.substring(3),
                card_name[0],
                card_name[1],
                card_cvv
            );
            Iugu.createPaymentToken(card_object, function(response) {
                if (response.errors) {
                    alert("Erro salvando cartão");
                } else {
                    $.ajax({
                        type: 'POST',
                        url: iugu_wc_credit_card_params.ajaxurl,
                        data: {
                            iugu_card_token: response.id
                        },
                        dataType: 'json',
                        processData: true,
                        success: function(response) {
                            location.href = iugu_wc_credit_card_params.redirect;
                        }
                    });
                }
            });
        });

        /**
         * Field mask in the credit card form.
         *
         * @returns void.
         */
        function formMasks() {
            if (iugu_wc_credit_card_params !== 'undefined') {
                $.each(iugu_wc_credit_card_params.masks, function(field, mask) {
                    $('#' + field).mask(mask);
                });
            } // end if;
        } // end formMasks;
    });
}(jQuery));