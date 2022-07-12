<?php
/**
 * Pix - Payment instructions.
 *
 * @author  Iugu
 * @package Iugu_WooCommerce/Templates
 * @version 1.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
} // end if;
?>

<div id="iugu-bank-slip-instructions">
	<p>
		<?php _e('After clicking on "Place order", you will have access to the QR Code, which you can read with your payment app.', IUGU); ?>
		<br />
		<?php _e('Note: The order will be confirmed only after the payment approval.', IUGU); ?>
	</p>

</div>