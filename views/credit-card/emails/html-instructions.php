<?php

/**
 * Credit Card - HTML email instructions.
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

<p class="order_details"><?php echo sprintf(__('Payment successfully made using credit card in %s.', IUGU), '<strong>' . $installments . 'x</strong>'); ?></p>