<?php

/**
 * Bank Slip - HTML email instructions.
 *
 * @author  Iugu
 * @package Iugu_WooCommerce/Templates
 * @version 1.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<h2><?php _e('Payment', IUGU); ?></h2>
<p class="order_details"><?php _e('Use the link below to view your bank slip. You can print and pay it on your internet banking or in a lottery retailer.', IUGU); ?><br /><a class="button" href="<?php echo esc_url($pdf); ?>" target="_blank"><?php _e('Pay the bank slip', IUGU); ?></a><br /><?php _e('After we receive the bank slip payment confirmation, your order will be processed.', IUGU); ?></p>