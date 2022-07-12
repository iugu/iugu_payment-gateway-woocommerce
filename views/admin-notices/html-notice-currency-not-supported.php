<?php

/**
 * Admin View: Notice - Currency not supported.
 */

if (!defined('ABSPATH')) {
	exit;
}

?>

<div class="error">
	<p><strong><?php _e('iugu disabled', IUGU); ?></strong>: <?php printf(__('Currency <code>%s</code> is not supported. WooCommerce iugu only works with Brazilian real (BRL).', IUGU), get_woocommerce_currency()); ?>
	</p>
</div>