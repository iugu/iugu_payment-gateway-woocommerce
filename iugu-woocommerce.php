<?php
/**
 * Plugin Name: WooCommerce iugu
 * Plugin URI: https://github.com/iugu/iugu-woocommerce
 * Description: iugu payment gateway for WooCommerce.
 * Author: iugu
 * Author URI: https://iugu.com/
 * Version: 2.2.1
 * Requires at least: 5.6
 * Requires PHP: 7.0
 * License: GPLv2 or later
 * Text Domain: iugu-woocommerce
 * Domain Path: lang/
 */

if (!defined( 'ABSPATH')) {

	exit;

} // end if;

if (!class_exists( 'WC_Iugu')) {

/**
 * WooCommerce Iugu main class.
 */
class WC_Iugu {

	/**
	 * API constants. Plugin name and version.
	 *
	 * @var string
	 */
	const CLIENT_NAME = 'plugin-iugu-woocommerce';

	const CLIENT_VERSION = '2.2.1';

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

		if (!defined('WC_IUGU_PLUGIN_FILE')) {

			define('WC_IUGU_PLUGIN_FILE', __FILE__ );

		} // end if;

		/**
		 * Load plugin text domain.
		 */
		add_action('init', array($this, 'load_plugin_textdomain'));

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

		} else {

			add_action('admin_notices', array($this, 'dependencies_notices'));

		} // end if;

	} // end __construct;

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
		include_once 'inc/gateways/credit-card/class-wc-iugu-credit-card-gateway.php';

		/**
		 * Credit Card Gateway.
		 */
		include_once 'inc/gateways/pix/class-wc-iugu-pix-gateway.php';

		/**
		 * My Account hooks.
		 */
		include_once 'inc/class-wc-iugu-hooks.php';

		new WC_Iugu_Hooks();

		if (class_exists('WC_Subscriptions_Order') || class_exists('WC_Pre_Orders_Order')) {

			if (!function_exists( 'wcs_create_renewal_order')) {

				/**
				 * Bank Slip - Subscriptions < 2.0
				 */
				include_once 'inc/gateways/bank-slip/class-wc-iugu-bank-slip-addons-gateway-deprecated.php';

				/**
				 * Credit Card - Subscriptions < 2.0
				 */
				include_once 'inc/gateways/credit-card/class-wc-iugu-credit-card-addons-gateway-deprecated.php';

			} else {

				include_once 'inc/gateways/bank-slip/class-wc-iugu-bank-slip-addons-gateway.php';

				include_once 'inc/gateways/credit-card/class-wc-iugu-credit-card-addons-gateway.php';

				include_once 'inc/gateways/pix/class-wc-iugu-pix-addons-gateway.php';

			} // end if;

		} // end if;

	} // end includes;

	/**
	 * Add all gateways to WooCommerce.
	 *
	 * @param  array $methods WooCommerce payment methods.
	 * @return array Payment methods with Iugu.
	 */
	public function add_gateway($methods) {

		if (class_exists('WC_Subscriptions_Order') || class_exists('WC_Pre_Orders_Order')) {

			if (!function_exists( 'wcs_create_renewal_order')) {

				$methods[] = 'WC_Iugu_Credit_Card_Addons_Gateway_Deprecated';

				$methods[] = 'WC_Iugu_Bank_Slip_Addons_Gateway_Deprecated';

			} else {

				$methods[] = 'WC_Iugu_Credit_Card_Addons_Gateway';

				$methods[] = 'WC_Iugu_Bank_Slip_Addons_Gateway';

				$methods[] = 'WC_Iugu_Pix_Addons_Gateway';

			} // end if;

		} else {

			$methods[] = 'WC_Iugu_Credit_Card_Gateway';

			$methods[] = 'WC_Iugu_Bank_Slip_Gateway';

			$methods[] = 'WC_Iugu_Pix_Gateway';

		} // end if;

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

	} // end dependencies_notices;

	/**
	 * Get log view.
	 *
	 * @return string Log view.
	 */
	public static function get_log_view($gateway_id) {

		if (defined( 'WC_VERSION') && version_compare(WC_VERSION, '2.2', '>=')) {

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

		if (class_exists('WC_Subscriptions_Order') || class_exists('WC_Pre_Orders_Order')) {

			if (!function_exists('wcs_create_renewal_order')) {

				$credit_card = 'wc_iugu_credit_card_addons_gateway_deprecated';

				$bank_slip   = 'wc_iugu_bank_slip_addons_gateway_deprecated';

			} else {

				$credit_card = 'wc_iugu_credit_card_addons_gateway';

				$bank_slip   = 'wc_iugu_bank_slip_addons_gateway';

				$pix = 'wc_iugu_pix_gateway';

			} // end if;

		} else  {

			$credit_card = 'wc_iugu_credit_card_Gateway';

			$bank_slip   = 'wc_iugu_bank_slip_gateway';

			$pix = 'wc_iugu_pix_gateway';

		} // end if;

		$plugin_links[] = '<a href="' . esc_url($settings_url . $credit_card) . '">' . __('Credit card settings', 'iugu-woocommerce') . '</a>';

		$plugin_links[] = '<a href="' . esc_url($settings_url . $bank_slip) . '">' . __('Bank slip settings', 'iugu-woocommerce') . '</a>';

		$plugin_links[] = '<a href="' . esc_url($settings_url . $pix) . '">' . __('PIX settings', 'iugu-woocommerce') . '</a>';

		return array_merge( $plugin_links, $links );

	} // end plugin_action_links;

} // end WC_Iugu;

/**
 * Add get instance to the plugins loaded hook;
 */
add_action( 'plugins_loaded', array( 'WC_Iugu', 'get_instance' ) );

} // end if;
