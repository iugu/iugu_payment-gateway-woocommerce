<?php

/**
 * PIX - Payment instructions.
 *
 * @author  iugu
 * @package views/piz
 * @version 1.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
} // end if;

?>

<?php if (isset($qrcode)) { ?>
	<div class="woocommerce-message">
		<span class="woocommerce-table woocommerce-table--order-details shop_table order_details">
			<div class="woocommerce-column woocommerce-column--1">
				<h3 class="woocommerce-column__title"><?php _e('PIX QRCode', IUGU); ?></h3>
				<div class="iugu-pix-qrcode-div">
					<img class="iugu-pix-qrcode" src="<?php echo esc_url($qrcode); ?>">
				</div>
			</div>
			<div class="woocommerce-column woocommerce-column--1">
				<input type="text" class="iugu-pix-text-input" value="<?php echo $qrcode_text; ?>" disabled="disabled" name="iugu_pix_qrcode_text" class="" />
				<button class="iugu-pix-text-button" id="iugu_pix_qrcode_text_button" data-clipboard-text='<?php echo $qrcode_text; ?>'><?php _e('Copy Code', IUGU); ?></button><br />
			</div>
		</span>
	</div>
<?php } else { ?>
	<div class="woocommerce-message">
		<span class="woocommerce-table woocommerce-table--order-details shop_table order_details">
			<div class="woocommerce-column woocommerce-column--1">
				<p>Token expirado</p>
			</div>
		</span>
	</div>
<?php } ?>
<div id="snackbar">PIX Copiado</div>