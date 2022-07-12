/*jshint devel: true */
(function ($) {
	'use strict';

	$(function () {
		$('#woocommerce_iugu-credit-card_pass_interest').on('change', function () {
			var fields = $('#woocommerce_iugu-credit-card_interest_rate_on_installment_1, ' +
				'#woocommerce_iugu-credit-card_interest_rate_on_installment_2, ' +
				'#woocommerce_iugu-credit-card_interest_rate_on_installment_3, ' +
				'#woocommerce_iugu-credit-card_interest_rate_on_installment_4, ' +
				'#woocommerce_iugu-credit-card_interest_rate_on_installment_5, ' +
				'#woocommerce_iugu-credit-card_interest_rate_on_installment_6, ' +
				'#woocommerce_iugu-credit-card_interest_rate_on_installment_7, ' +
				'#woocommerce_iugu-credit-card_interest_rate_on_installment_8, ' +
				'#woocommerce_iugu-credit-card_interest_rate_on_installment_9, ' +
				'#woocommerce_iugu-credit-card_interest_rate_on_installment_10, ' +
				'#woocommerce_iugu-credit-card_interest_rate_on_installment_11, ' +
				'#woocommerce_iugu-credit-card_interest_rate_on_installment_12')
				.closest('tr');
			if ($(this).is(':checked')) {
				fields.show();
			} else {
				fields.hide();
			}

		}).change();
	});

}(jQuery));