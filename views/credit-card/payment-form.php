<?php

/**
 * Credit Card - Checkout form.
 *
 * @author  Iugu
 * @package Iugu_WooCommerce/Templates
 * @version 1.1.0
 */
if (!defined('ABSPATH')) {
	exit;
} // end if;
?>

<fieldset id="iugu-credit-card-fields">

	<?php if (WC()->checkout()->is_registration_required()) { ?>
		<input type="hidden" value="1" id="iugu-is_registration_required">
	<?php } else { ?>
		<input type="hidden" value="0" id="iugu-is_registration_required">
	<?php } ?>

	<?php if ('yes' === get_option('woocommerce_enable_signup_and_login_from_checkout') || $registration_required) { ?>
		<input type="hidden" value="1" id="iugu-woocommerce_enable_signup_and_login_from_checkout">
	<?php } else { ?>
		<input type="hidden" value="0" id="iugu-woocommerce_enable_signup_and_login_from_checkout">
	<?php } ?>


	<?php if (is_user_logged_in()) { ?>
		<input type="hidden" value="1" id="iugu-is_user_logged_in">
	<?php } else { ?>
		<input type="hidden" value="0" id="iugu-is_user_logged_in">
	<?php } ?>

	<?php if (isset($installments) && $installments > 0) { ?>
		<p class=" form-row form-row-wide" style="<?php if ($installments == 1) echo "visibility: hidden;position: absolute; opacity: 0;" ?> ">
			<label for="iugu-card-installments"><?php _e('Installments', IUGU); ?> <span class="required">*</span></label>
			<select id="iugu-card-installments" onchange="iugu_card_installments_onchange(this);" name="iugu_card_installments" style="font-size: 1.5em; padding: 4px; width: 100%;">
				<?php if (!isset($fixed_installments) || $fixed_installments == 0) { ?>
					<option value=""><?php echo __('Select', IUGU); ?></option>
				<?php } ?>
				<?php for ($i = 1; $i <= $installments; $i++) :
					$total_to_pay = $order_total;
					$installment_total = $total_to_pay / $i;
					$interest_text = __('free interest', IUGU);
					/**
					 * Set the interest rate.
					 */
					if ($pass_interest == 'yes') {
						$total_rate = (($total_to_pay / 100) * $rates[$i]);
						$total_to_pay = $total_to_pay + $total_rate;
						$installment_total = ($total_to_pay / $i);
						if ($rates[$i] > 0) {
							$interest_text = __('with interest', IUGU);
						}
					} // end if;
					/**
					 * Stop when the installment total is less than the smallest installment configure.
					 */
					if ($i > 1 && $installment_total < $smallest_installment) {
						break;
					} // end if;
				?>
					<?php if (isset($fixed_installments) && $fixed_installments == $i) { ?>
						<option value="<?php echo $i; ?>"><?php echo esc_attr(sprintf(__('%dx of %s %s (Total: %s)', IUGU), $i, sanitize_text_field(wc_price($installment_total)), $interest_text, sanitize_text_field(wc_price($total_to_pay)))); ?></option>
					<?php } ?>
					<?php if (!isset($fixed_installments) || $fixed_installments == 0) { ?>
						<option value="<?php echo $i; ?>"><?php echo esc_attr(sprintf(__('%dx of %s %s (Total: %s)', IUGU), $i, sanitize_text_field(wc_price($installment_total)), $interest_text, sanitize_text_field(wc_price($total_to_pay)))); ?></option>
					<?php } ?>
				<?php endfor; ?>
			</select>
		</p>
	<?php } else { ?>
		<input type="hidden" value="1" id="iugu-card-installments" name="iugu_card_installments">
	<?php } ?>

	<div style="margin-bottom: 1em;"></div>

	<?php if (isset($payment_methods) && count($payment_methods) == 0) : ?>
		<select id="customer-payment-method-id" name="customer_payment_method_id" style="display: none;">
			<option value=""><?php echo __('New credit card', IUGU); ?></option>
		</select>
	<?php endif; ?>
	<?php if (isset($payment_methods) && count($payment_methods) > 0) : ?>
		<p class="form-row form-row-wide">
			<select id="customer-payment-method-id" name="customer_payment_method_id" style="font-size: 1.5em; padding: 4px; width: 100%;">
				<?php
				foreach ($payment_methods as $payment_method) :
					echo '<option value="' . $payment_method->get_token() . '" ' . ($payment_method->is_default() ? 'selected' : '') . '>' .	$payment_method->get_card_type() . ' ' . $payment_method->get_last4() . ' (Vencto. ' . $payment_method->get_expiry_month() . '/' . $payment_method->get_expiry_year() . ')' . '</option>';
				endforeach;
				?>
				<option value=""><?php echo __('New credit card', IUGU); ?></option>
			</select>
		</p>
	<?php endif; ?>

	<div class="clear"></div>

	<div id="new-credit-card">
		<p class="form-row">
			<label for="iugu-card-number">
				<?php _e('Card number', IUGU); ?>
				<span class="required">*</span>
			</label>
			<input id="iugu-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" data-iugu="number" />
		</p>
		<p class="form-row">
			<label for="iugu-card-holder-name">
				<?php _e('Name printed on card', IUGU); ?>
				<span class="required">*</span>
			</label>
			<input id="iugu-card-holder-name" name="iugu_card_holder_name" class="input-text" type="text" autocomplete="off" data-iugu="full_name" />
		</p>
		<div class="clear"></div>
		<p class="form-row form-row-first">
			<label for="iugu-card-expiry">
				<?php _e('Expiry date', IUGU); ?>
				<span class="required">*</span>
			</label>
			<input id="iugu-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="<?php _e('MM / YYYY', IUGU); ?>" data-iugu="expiration" />
		</p>
		<p class="form-row form-row-last">
			<label for="iugu-card-cvc"><?php _e('Security code', IUGU); ?> <span class="required">*</span></label>
			<input id="iugu-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="<?php _e('CVC', IUGU); ?>" data-iugu="verification_value" />
		</p>
		<div class="clear"></div>
		<?php if (isset($order_total)) : ?>
			<p class="form-row  form-row-first">
				<?php if ($registration_required) { ?>
					<input type="checkbox" id="iugu-save-card" checked hidden placeholder="<?php _e('To save the card you need to create an account.', IUGU); ?>" name="iugu_save_card">
				<?php } else { ?>
					<input type="checkbox" class="input-checkbox" id="iugu-save-card" placeholder="<?php _e('To save the card you need to create an account.', IUGU); ?>" name="iugu_save_card"> <label for="iugu-save-card"><?php _e('Save this credit card', IUGU); ?></label>
				<?php } ?>
			</p>
			<p class="form-row  form-row-first" id="p-iugu-save-default">
				<input type="checkbox" class="input-checkbox" id="iugu-save-default" name="iugu_save_default"> <label for="iugu-save-default"><?php _e('Use with default', IUGU); ?></label>
			</p>
		<?php endif; ?>
	</div>

	<div class="clear"></div>
	<div style="margin-bottom: 1em;"></div>
</fieldset>

<script>
	$latValue = "--";

	function iugu_card_installments_onchange(sel) {
		if ($latValue !== sel.value) {
			$latValue = sel.value;
			jQuery('body').trigger('update_checkout');
		}
	}
	var iugu_save_card = document.getElementById("iugu-save-card");
	if (iugu_save_card) {
		var disableCheckboxConditioned = function() {
			if (iugu_save_card.checked) {
				jQuery('#p-iugu-save-default').show();
			} else {
				jQuery('#p-iugu-save-default').hide();
			}
		}
		iugu_save_card.onclick = disableCheckboxConditioned;
		disableCheckboxConditioned();

		var customer_payment_method_id = document.getElementById("customer-payment-method-id");
		var hideShowNewCreditCard = function() {
			var v = customer_payment_method_id.value;
			if (v == "" || v == undefined) {
				jQuery('#new-credit-card').show();
			} else {
				jQuery('#new-credit-card').hide();
			}
		}
		setTimeout(() => {
			hideShowNewCreditCard();
		}, 100);
		customer_payment_method_id.onchange = hideShowNewCreditCard;
	}
</script>