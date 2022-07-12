<?php if ('yes' == get_option('woocommerce_enable_guest_checkout')) { ?>
    <div class="notice notice-warning">
        <p><strong><?php _e('Allow customers to place orders without an enabled account. Option must be analyzed.', IUGU); ?></strong><a href='./admin.php?page=wc-settings&tab=account'> Link</a></p>
    </div>
<?php } ?>

<?php if ('yes' !== get_option('woocommerce_enable_signup_and_login_from_checkout')) { ?>
    <div class="notice notice-warning">
        <p><strong><?php _e('Allow customers to create an account during checkout not enabled. Option must be analyzed.', IUGU); ?></strong><a href='./admin.php?page=wc-settings&tab=account'> Link</a></p>
    </div>
<?php } ?>