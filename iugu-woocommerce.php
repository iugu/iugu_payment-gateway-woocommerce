<?php

/**
 * Plugin Name: IUGU Payment Gateway
 * Plugin URI: https://github.com/iugu/iugu-woocommerce
 * Description: iugu payment gateway for WooCommerce.
 * Author: iugu
 * Author URI: https://iugu.com/
 * Version: 3.0.0.0
 * Requires at least: 5.6
 * Requires PHP: 7.0
 * License: GPLv2 or later
 * Text Domain: iugu-woocommerce
 * Domain Path: lang/
 */

if (!defined('ABSPATH')) {
	exit;
} // end if;

if (!class_exists('WC_Iugu2')) {

	/**
	 * WooCommerce Iugu main class.
	 */
	class WC_Iugu2 {
		/**
		 * API constants. Plugin name and version.
		 *
		 * @var string
		 */
		const CLIENT_NAME = 'plugin-iugu-woocommerce-payment-gateway';

		const CLIENT_VERSION = '3.0.0.0';

		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Initialize the plugin actions.
		 */
		public function __construct() {
			WC()->frontend_includes();
			if (!defined('WC_IUGU_PLUGIN_FILE')) {
				define('WC_IUGU_PLUGIN_FILE', __FILE__);
			} // end if;
			/**
			 * Load plugin text domain.
			 */
			add_action('init', array($this, 'load_plugin_textdomain'));
			if (!class_exists('WC_Iugu')) {
				/**
				 * Checks with WooCommerce and WooCommerce Extra Checkout Fields for Brazil is installed.
				 */
				if (class_exists('WC_Payment_Gateway') && class_exists('Extra_Checkout_Fields_For_Brazil')) {
					$this->includes();
					/**
					 * Hook to add Iugu Gateway to WooCommerce.
					 */
					add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));
					add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));
				}
				add_action('admin_notices', array($this, 'dependencies_notices'));

				add_filter('woocommerce_get_customer_payment_tokens', array($this, 'woocommerce_get_customer_payment_tokens'), 10, 3);
				if (function_exists('bua_login_authenticate')) {
					add_action('woocommerce_edit_account_form',  array($this, 'woocommerce_edit_account_form_block_user'));
					add_action('woocommerce_save_account_details', array($this, 'woocommerce_save_account_details_block_user'));
				}
			} else {
				include_once 'views/admin-notices/html-notice-remove-old-version.php';
			} // end if;

		} // end __construct;

		function woocommerce_get_customer_payment_tokens($tokens, $customer_id, $gateway_id) {
			if (is_array($tokens) && count($tokens) == 0) {
				$api = new WC_Iugu_API2(null, 'credit-card');
				$customer_id_iugu = $api->get_customer_id();
				if ($customer_id_iugu) {
					$payment_methods_iugu = $api->get_iugu_customer_payment_methods($customer_id_iugu);
					if (is_array($payment_methods_iugu) && count($payment_methods_iugu) > 0) {
						foreach ($payment_methods_iugu as $token_info) {
							if ($token_info['item_type'] == 'credit_card') {
								$api->set_wc_payment_method($token_info, false, $customer_id_iugu);
							}
						}
						$tokens = WC_Iugu_API2::get_payment_methods();
					}
				}
			}
			return $tokens;
		}

		function woocommerce_edit_account_form_block_user() {
			if (defined('DOING_AJAX') && DOING_AJAX) {
				return;
			}
			wp_enqueue_style('user_status_style', BUA_CSS_URL . '/style.css');
			$user_id = wp_get_current_user()->ID;
?>
			<label class="tgl">
				<input type="checkbox" name="user_status" value="deactive" id="user_status" <?php checked(get_user_meta($user_id, 'user_status', true), 'deactive'); ?>>
				<span data-off="<?php echo __('Active account', 'iugu-woocommerce') ?>" data-on="<?php echo __('Request deletion', 'iugu-woocommerce') ?>"></span>
			</label>
			</p>
<?php
		}

		function woocommerce_save_account_details_block_user($user_ID) {
			if (defined('DOING_AJAX') && DOING_AJAX) {
				return;
			}
			$user_status = !empty($_POST['user_status']) ? wc_clean($_POST['user_status']) : '';
			$user_status_message = '';
			if ($user_status == 'deactive') {
				$user_status_message = __('Deletion request made. Wait for the process to complete.', 'iugu-woocommerce');
			}
			update_user_meta($user_ID, 'user_status', $user_status);
			update_user_meta($user_ID, 'user_status_message', $user_status_message);
		}

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			/**
			 * If the single instance hasn't been set, set it now.
			 */
			if (null == self::$instance) {
				self::$instance = new self;
			} // end if;
			return self::$instance;
		} // end get_instance;

		/**
		 * Get templates path.
		 *
		 * @return string Templates dir path.
		 */
		public static function get_templates_path() {
			return plugin_dir_path(__FILE__) . 'views/';
		} // end get_templates_path;

		/**
		 * Load the plugin text domain for translation.
		 *
		 * @return void.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain('iugu-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/lang/');
		} // end load_plugin_textdomain;

		/**
		 * Include all the necessary files.
		 *
		 * @return void
		 */
		private function includes() {
			include_once 'inc/gateways/class-wc-iugu-gateway.php';
			include_once 'inc/gateways/class-wc-iugu-gateway-woocommerce-subscription.php';

			/**
			 * Main API file.
			 */
			include_once 'inc/class-wc-iugu-api.php';

			/**
			 * Bank Slip Gateway.
			 */
			include_once 'inc/gateways/bank-slip/class-wc-iugu-bank-slip-gateway.php';

			/**
			 * Credit Card Gateway.
			 */
			include_once 'inc/gateways/credit-card/class-wc-iugu-credit-card-gateway-woocommerce-subscription.php';
			include_once 'inc/gateways/credit-card/class-wc-iugu-credit-card-gateway.php';

			/**
			 * PIX Gateway.
			 */
			include_once 'inc/gateways/pix/class-wc-iugu-pix-gateway.php';

			/**
			 * My Account hooks.
			 */
			include_once 'inc/class-wc-iugu-hooks.php';

			new WC_Iugu_Hooks2();
		} // end includes;

		/**
		 * Add all gateways to WooCommerce.
		 *
		 * @param  array $methods WooCommerce payment methods.
		 * @return array Payment methods with Iugu.
		 */
		public function add_gateway($methods) {
			$methods[] = 'WC_Iugu_Credit_Card_Gateway2';
			$methods[] = 'WC_Iugu_Bank_Slip_Gateway2';
			$methods[] = 'WC_Iugu_Pix_Gateway2';
			return $methods;
		} // end add_gateway;

		/**
		 * Add dependencies notices, if needed.
		 *
		 * @return void.
		 */
		public function dependencies_notices() {
			if (!class_exists('WC_Payment_Gateway')) {
				include_once 'views/admin-notices/html-notice-woocommerce-missing.php';
			} // end if;
			if (!class_exists('Extra_Checkout_Fields_For_Brazil')) {
				include_once 'views/admin-notices/html-notice-ecfb-missing.php';
			} // end if;
			include_once 'views/admin-notices/html-notice-woocommerce-account.php';
		} // end dependencies_notices;

		/**
		 * Get log view.
		 *
		 * @return string Log view.
		 */
		public static function get_log_view($gateway_id) {
			if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.2', '>=')) {
				return '<a href="' . esc_url(admin_url('admin.php?page=wc-status&tab=logs&log_file=' . esc_attr($gateway_id) . '-' . sanitize_file_name(wp_hash($gateway_id)) . '.log')) . '">' . __('System status &gt; logs', 'iugu-woocommerce') . '</a>';
			} // end if;
			return '<code>woocommerce/logs/' . esc_attr($gateway_id) . '-' . sanitize_file_name(wp_hash($gateway_id)) . '.txt</code>';
		} // end get_log_view;

		/**
		 * Action links.
		 *
		 * @param  array $links
		 * @return array
		 */
		public function plugin_action_links($links) {
			$plugin_links = array();
			if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.1', '>=')) {
				$settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=');
			} else {
				$settings_url = admin_url('admin.php?page=woocommerce_settings&tab=payment_gateways&section=');
			} // end if;
			$credit_card = 'WC_Iugu_Credit_Card_Gateway2';
			$bank_slip   = 'WC_Iugu_Bank_Slip_Gateway2';
			$pix = 'WC_Iugu_Pix_Gateway2';
			$plugin_links[] = '<a href="' . esc_url($settings_url . $credit_card) . '">' . __('Credit card settings', 'iugu-woocommerce') . '</a>';
			$plugin_links[] = '<a href="' . esc_url($settings_url . $bank_slip) . '">' . __('Bank slip settings', 'iugu-woocommerce') . '</a>';
			$plugin_links[] = '<a href="' . esc_url($settings_url . $pix) . '">' . __('PIX settings', 'iugu-woocommerce') . '</a>';
			return array_merge($plugin_links, $links);
		} // end plugin_action_links;
	} // end WC_Iugu2;

	/**
	 * Add get instance to the plugins loaded hook;
	 */
	add_action('plugins_loaded', array('WC_Iugu2', 'get_instance'));
} // end if;
