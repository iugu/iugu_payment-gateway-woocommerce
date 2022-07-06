<?php
/**
 * Credit Card - Checkout form.
 *
 * @author  Iugu
 * @package Iugu_WooCommerce/Templates
 * @version 1.1.0
 */

if (!defined( 'ABSPATH')) {

	exit;

} // end if;

?>

<fieldset id="iugu-credit-card-fields">

<?php if (isset($payment_methods) && count($payment_methods) > 0) : ?>

	<p class="form-row form-row-wide">

		<select id="customer-payment-method-id" name="customer_payment_method_id" style="font-size: 1.5em; padding: 4px; width: 100%;">

			<?php

				foreach($payment_methods as $payment_method) :

					echo '<option value="' . $payment_method->get_id() . '" ' .($payment_method->get_id() == $default_method ? 'selected' : '') . '>' .	$payment_method->get_card_type() . ' ' . $payment_method->get_last4() . '</option>';

				endforeach;

			?>

			<option value=""><?php echo __('New credit card', 'iugu-woocommerce'); ?></option>

		</select>

	</p>

<?php endif; ?>

	<div id="new-credit-card" <?php if (isset($payment_methods) && $payment_methods) { echo 'style="display:none;"'; } else { echo ''; }?>>

		<p class="form-row">

			<label for="iugu-card-number">

				<?php _e('Card number', 'iugu-woocommerce'); ?>
				<span class="required">*</span>

			</label>

			<input id="iugu-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" data-iugu="number" />

		</p>

		<p class="form-row">

			<label for="iugu-card-holder-name">

				<?php _e('Name printed on card', 'iugu-woocommerce'); ?>
				<span class="required">*</span>

			</label>

			<input id="iugu-card-holder-name" name="iugu_card_holder_name" class="input-text" type="text" autocomplete="off" data-iugu="full_name" />

		</p>

		<div class="clear"></div>

		<p class="form-row form-row-first">

			<label for="iugu-card-expiry">

				<?php _e('Expiry date', 'iugu-woocommerce'); ?>
				<span class="required">*</span>

			</label>

			<input id="iugu-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="<?php _e( 'MM / YYYY', 'iugu-woocommerce' ); ?>"  data-iugu="expiration" />

		</p>

		<p class="form-row form-row-last">

			<label for="iugu-card-cvc"><?php _e( 'Security code', 'iugu-woocommerce' ); ?> <span class="required">*</span></label>
			<input id="iugu-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="<?php _e( 'CVC', 'iugu-woocommerce' ); ?>" data-iugu="verification_value" />
		</p>
		<div class="clear"></div>

		<?php if (isset($order_total)) : ?>

			<p>

				<input type="checkbox" id="iugu-save-card" name="iugu_save_card"> <label for="iugu-save-card"><?php _e('Save this credit card', 'iugu-woocommerce'); ?></label>

			</p>

		<?php endif; ?>

	</div>

	<?php if (isset($installments) && $installments > 0) { ?>

	<p class="form-row form-row-wide">

		<label for="iugu-card-installments"><?php _e( 'Installments', 'iugu-woocommerce' ); ?> <span class="required">*</span></label>

		<select id="iugu-card-installments" name="iugu_card_installments" style="font-size: 1.5em; padding: 4px; width: 100%;">

			<option value=""><?php echo __('Select', 'iugu-woocommerce'); ?></option>

			<?php for ($i = 1; $i <= $installments; $i++) :

				$total_to_pay = $order_total;

				$installment_total = $total_to_pay / $i;

				$interest_text = __('free interest', 'iugu-woocommerce' );

				/**
				 * Set the interest rate.
				 */
				if ($i > $free_interest) {

					$total_rate = (($total_to_pay / 100) * $rates[$i]);

					$total_to_pay = $total_to_pay + $total_rate;

					$installment_total = ($total_to_pay / $i);

					$interest_text     = __( 'with interest', 'iugu-woocommerce' );

				} // end if;

				/**
				 * Stop when the installment total is less than the smallest installment configure.
				 */
				if ($i > 1 && $installment_total < $smallest_installment) {

					break;

				} // end if;

				?>

				<option value="<?php echo $i; ?>"><?php echo esc_attr( sprintf( __( '%dx of %s %s (Total: %s)', 'iugu-woocommerce' ), $i, sanitize_text_field( wc_price( $installment_total ) ), $interest_text, sanitize_text_field( wc_price( $total_to_pay ) ) ) ); ?></option>

			<?php endfor; ?>

		</select>

	</p>

	<?php } else { ?>

		<input type="hidden" value="1" id="iugu-card-installments" name="iugu_card_installments">

	<?php } ?>

	<div class="clear"></div>

</fieldset>
