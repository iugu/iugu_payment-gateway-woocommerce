<?php

/**
 * Bank Slip - Plain email instructions.
 *
 * @author  Iugu
 * @package Iugu_WooCommerce/Templates
 * @version 1.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
_e('Payment', IUGU);
echo "\n\n";
_e('Use the link below to view your bank slip. You can print and pay it on your internet banking or in a lottery retailer.', IUGU);
echo "\n";
echo esc_url($pdf);
echo "\n";
_e('After we receive the bank slip payment confirmation, your order will be processed.', IUGU);
echo "\n\n****************************************************\n\n";
