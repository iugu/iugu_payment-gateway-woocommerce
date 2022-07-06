<?php
/**
 * WC iugu API Class.
 */
class WC_Iugu_API {

  /**
   * API URL.
   *
   * @var string
   */
  protected $api_url = 'https://api.iugu.com/v1/';

  /**
   * JS Library URL.
   *
   * @var string
   */
  protected $js_url = 'https://js.iugu.com/v2.js';

  /**
   * Gateway class.
   *
   * @var WC_Iugu_Gateway
   */
  protected $gateway;

  /**
   * Payment method.
   *
   * @var string
   */
  protected $method = '';

  /**
   * Constructor class.
   *
   * @param WC_Iugu_Gateway $gateway WooCommerce Gateway Object
   * @return void.
   */
  public function __construct($gateway = null, $method = '') {

    $this->gateway = $gateway;

    $this->method  = $method;

  } // end __construct;

  /**
   * Get API URL.
   *
   * @return string API Url.
   */
  public function get_api_url() {

    return $this->api_url;

  } // end get_api_url;

  /**
   * Get JS Library URL.
   *
   * @return string
   */
  public function get_js_url() {

    return $this->js_url;

  } // end if;

  /**
   * Get WooCommerce return URL.
   *
   * @return string
   */
  protected function get_wc_request_url() {

    global $woocommerce;

    if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.1', '>=')) {

      return WC()->api_request_url(get_class($this->gateway));

    } else {

      return $woocommerce->api_request_url(get_class($this->gateway));

    } // end if;

  } // end get_wc_request_url;

  /**
   * Get the settings URL.
   *
   * @return string
   */
  public function get_settings_url() {

    if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.1', '>=')) {

      return admin_url('admin.php?page=wc-settings&tab=checkout&section=' . strtolower( get_class($this->gateway)));

    } // end if;

    return admin_url('admin.php?page=woocommerce_settings&tab=payment_gateways&section=' . get_class($this->gateway));

  } // end get_settings_url;

  /**
   * Get iugu account configuration.
   *
   * @return void
   */
  public function get_account_configuration() {

    $endpoint = 'accounts/' . $this->gateway->account_id  . '/';

    $response = $this->do_request($endpoint, 'GET');

    if (is_object($response) && is_wp_error($response)) {

      if ($this->gateway->debug === 'yes') {

        $this->gateway->log->add($this->gateway->id, 'WP_Error while trying to get account configuration: ' . $response['errors']);

      } // end if;

      return array(
        'errors' => $response['errors']
      );

    } else {

      if ($this->gateway->debug === 'yes') {

        $this->gateway->log->add($this->gateway->id, '');

      } // end if;

      $body = json_decode($response['body'], true);


      if (isset($body['configuration'])) {

        return $body['configuration'];

      } // end if;

    } // end if;

  } // end get_account_configuration;

  /**
   * Get transaction rate.
   *
   * @return float
   */
  public function get_transaction_rate() {

    $rate = isset($this->gateway->transaction_rate) ? $this->gateway->transaction_rate : 7;

    return wc_format_decimal($rate);

  } // end get_transaction_rate;

  /**
   * Get iugu credit card interest rates.
   *
   * @return array
   */
  public function get_interest_rate() {

    $rates = apply_filters('iugu_woocommerce_interest_rates', array(
      '1'  => 2.51,
      '2'  => 3.21,
      '3'  => 3.21,
      '4'  => 3.21,
      '5'  => 3.21,
      '6'  => 3.21,
      '7'  => 3.55,
      '8'  => 3.55,
      '9'  => 3.55,
      '10' => 3.55,
      '11' => 3.55,
      '12' => 3.55,
    ));

    return $rates;

  } // end get_interest_rate;

  /**
   * Get account max installments configuration.
   *
   * @return int
   */
  public function get_max_installments() {

    $account_configuration = $this->get_account_configuration();

    if ($account_configuration) {

      $max_installments = $account_configuration['credit_card']['max_installments'];

      return intval($max_installments);

    } // end if;

  } // end get_max_installments;

  /**
   * Get account max installments without interest configuration.
   *
   * @return int
   */
  public function get_max_installments_without_interest() {

    $account_configuration = $this->get_account_configuration();

    if ($account_configuration) {

      $max_installments_without_interest = $account_configuration['credit_card']['max_installments_without_interest'];

      return intval($max_installments_without_interest);

    } // end if;

  } // end get_max_installments_without_interest;

  /**
   * Get order total.
   *
   * @return float
   */
  public function get_order_total() {

    global $woocommerce;

    $order_total = 0;

    if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.1', '>=')) {

      $order_id = absint(get_query_var('order-pay'));

    } else {

      $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;

    } // end if;

    /**
     * Gets order total from "pay for order" page.
     */
    if (0 < $order_id) {

      $order = new WC_Order($order_id);

      $order_total = (float) $order->get_total();

    /**
     * Gets order total from cart/checkout.
     */
    } elseif (0 < $woocommerce->cart->total) {

      $order_total = (float) $woocommerce->cart->total;

    } // end if;

    return $order_total;

  } // end get_order_total;

  /**
   * Returns a bool that indicates if currency is amongst the supported ones.
   *
   * @return bool
   */
  public function using_supported_currency() {

    return get_woocommerce_currency() == 'BRL';

  } // end using_supported_currency;

  /**
   * Check if order contains subscriptions.
   *
   * @param  int $order_id
   * @return bool
   */
  public function order_contains_subscription($order_id) {

    if (class_exists('WC_Subscriptions_Order') && function_exists('wcs_order_contains_subscription')) {

      return wcs_order_contains_subscription($order_id) || wcs_order_contains_resubscribe($order_id);

    } elseif (class_exists('WC_Subscriptions_Order')) {

      return WC_Subscriptions_Order::order_contains_subscription($order_id) || WC_Subscriptions_Renewal_Order::is_renewal($order_id);

    } // end if;

    return false;

  } // end order_contains_subscription;

  /**
   * Check if order contains pre-orders.
   *
   * @param  int $order_id
   * @return bool
   */
  public function order_contains_pre_order($order_id) {

    return class_exists('WC_Pre_Orders_Order') && WC_Pre_Orders_Order::order_contains_pre_order($order_id);

  } // end order_contains_pre_order;

  /**
   * Only numbers.
   *
   * @param  string|int $string To filter.
   * @return string|int String or int with only numbers.
   */
  protected function only_numbers($string) {

    return preg_replace( '([^0-9])', '', $string );

  } // end only_numbers;

  /**
   * Add error message in checkout.
   *
   * @param  string $message Error message.
   * @return string Displays the error message.
   */
  public function add_error($message) {

    global $woocommerce;

    if (function_exists('wc_add_notice')) {

      wc_add_notice($message, 'error');

    } else {

      $woocommerce->add_error($message);

    } // end if;

  } // end add_error;

  /**
   * Send email notification.
   *
   * @param string $subject Email subject.
   * @param string $title   Email title.
   * @param string $message Email message.
   * @return void.
   */
  public function send_email($subject, $title, $message) {

    global $woocommerce;

    if (defined('WC_VERSION') && version_compare( WC_VERSION, '2.1', '>=')) {

      $mailer = WC()->mailer();

    } else {

      $mailer = $woocommerce->mailer();

    } // end if;

    $mailer->send(get_option('admin_email'), $subject, $mailer->wrap_message($title, $message));

  } // end send_email;

  /**
   * Empty a cart to empty the card.
   *
   * @return void
   */
  public function empty_card() {

    global $woocommerce;

    /**
     * Empty cart.
     */
    if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.1', '>=')) {

      WC()->cart->empty_cart();

    } else {

      $woocommerce->cart->empty_cart();

    } // end if;

  } // end empty_card;

  /**
   * Get customer payment method ID.
   *
   * @return string Payment Method ID.
   */
  public function get_customer_payment_method_id() {

    $customer_id = get_user_meta(get_current_user_id(), '_iugu_customer_id', true);

    $endpoint = 'customers/' . $customer_id;

    $response = $this->do_request($endpoint, 'GET');

    if (is_object($response) && is_wp_error($response)) {

      if ($this->gateway->debug === 'yes') {

        $this->gateway->log->add($this->gateway->id, 'WP_Error while trying to get the customer payment method id: ' . $response->get_error_message());

      } // end if;

      return array(
        'errors' => $response->get_error_message()
      );

    } else {

      $body = json_decode($response['body'], true);

      if ($this->gateway->debug === 'yes') {

        $this->gateway->log->add($this->gateway->id, print_r($body, TRUE));

      } // end if;

      if (isset($body['default_payment_method_id'])) {

        return $body['default_payment_method_id'];

      } // end if;

    } // end if;

  } // end get_customer_payment_method_id;

  /**
   * GET the iugu subscription object.
   *
   * @since 2.20
   *
   * @param int $plan_id
   * @return json with the request response
   */
  public function get_iugu_subscription($iugu_subscription_id) {

    $endpoint = 'subscriptions/' . $iugu_subscription_id . '/';

    $response = $this->do_request($endpoint, 'GET');

    if (is_object($response) && is_wp_error($response)) {

      if ($this->gateway->debug == 'yes') {

        $this->gateway->log->add( $this->gateway->id, 'WP_Error while trying to get the subscription: ' . $response->get_error_message());

      } // end if

      return array(
        'errors' => $response->get_error_message()
      );

    } elseif (200 == $response['response']['code'] && 'OK' == $response['response']['message']) {

      $body = json_decode($response['body'], true);

      if ($this->gateway->debug === 'yes') {

        $this->gateway->log->add($this->gateway->id, $body);

      } // end if;

      return $body;

    } // end if;

  } // end get_iugu_subscription;

  /**
   * POST to change subscription status to 'suspend'.
   *
   * @since 2.20
   *
   * @param int $subscription_id Iugu Subscription ID.
   * @return json API Request response body.
   */
  public function suspend_iugu_subscription($iugu_subscription_id) {

    $endpoint = 'subscriptions/' . $iugu_subscription_id . '/suspend/';

    $response = $this->do_request($endpoint, 'POST');

    $body = json_decode($response['body'], true);

    if ($this->gateway->debug === 'yes') {

      $this->gateway->log->add($this->gateway->id, $body);

    } // end if;

    return $body;

  } // end suspend_iugu_subscription;

  /**
   * POST to change subscription status to 'active' after being suspended.
   *
   * @since 2.20
   *
   * @param int $subscription_id Iugu Subscription ID.
   * @return json API Request response body.
   */
  public function activate_iugu_subscription($iugu_subscription_id) {

    $endpoint = 'subscriptions/' . $iugu_subscription_id . '/activate/';

    $response = $this->do_request($endpoint, 'POST');

    $body = json_decode($response['body'], true);

    if ($this->gateway->debug === 'yes') {

      $this->gateway->log->add($this->gateway->id, $body);

    } // end if;

    return $body;

  } // end activate_iugu_subscription;

  /**
   * POST to change subscription status to 'suspended : false' after being unsuspended.
   *
   * @since 2.20
   *
   * @param int $subscription_id Iugu Subscription ID.
   * @return json API Request response body.
   */
  public function unsuspend_iugu_subscription($iugu_subscription_id) {

    $endpoint = 'subscriptions/' . $iugu_subscription_id;

    $params = $this->build_api_params(array(
      'suspended' => false,
      'active'		=> true
    ));

    $response = $this->do_request($endpoint, 'PUT', $params);

    $body = json_decode($response['body'], true);

    if ($this->gateway->debug === 'yes') {

      $this->gateway->log->add($this->gateway->id, $body);

    } // end if;

    return $body;

  } // end unsuspend_iugu_subscription;

  /**
   * DELETE a iugu subscription.
   *
   * @since 2.20
   *
   * @param int $subscription_id Iugu Subscription ID.
   * @return json API Request response body.
   */
  public function delete_iugu_subscription($iugu_subscription_id) {

    $endpoint = 'subscriptions/' . $iugu_subscription_id;

    $response = $this->do_request($endpoint, 'DELETE');

    $body = json_decode($response['body'], true);

    if ($this->gateway->debug === 'yes') {

      $this->gateway->log->add($this->gateway->id, $body);

    } // end if;

    return $body;

  } // end delete_iugu_subscription;

  /**
   * DELETE a payment method subscription.
   *
   * @since 2.20
   *
   * @param int $customer_id The Customer ID.
   * @param int $payment_id  The Payment method ID.
   * @return mixed.
   */
  public function delete_customer_payment_method($customer_id, $payment_id) {

    $endpoint = 'customers/' . $customer_id . '/payment_methods/' . $payment_id;

    $iugu_options = get_option('woocommerce_iugu-bank-slip_settings');

    if (is_array($iugu_options) && isset($iugu_options['api_token'])) {

      $response = $this->do_request($endpoint, 'DELETE', array(), array(), $iugu_options['api_token']);

      $body = json_decode($response['body'], true);

      return $body;

    } // end if;

  } // end delete_subscription;

  /**
   * Do requests to the iugu API.
   *
   * @param  string $endpoint API Endpoint.
   * @param  string $method   Request method.
   * @param  array  $data     Request data.
   * @param  array  $headers  Request headers.
   * @return array  Request response.
   */
  protected function do_request($endpoint, $method = 'POST', $data = array(), $headers = array(), $api_token = '') {

    if (!$api_token) {

      $api_token = $this->gateway->api_token;

    } // end if;

    $params = array(
      'method'    => $method,
      'sslverify' => false,
      'timeout'   => 60,
      'headers'   => array(
        'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
        'Authorization' => 'Basic ' . base64_encode($api_token . ':x')
      )
    );

    if (!empty($data)) {

      $params['body'] = $data . '&client_name=' . WC_Iugu::CLIENT_NAME . '&client_version=' . WC_Iugu::CLIENT_VERSION;

    } // end if;

    if (!empty($headers)) {

      $params['headers'] = $headers;

    } // end if;

    return wp_remote_request($this->get_api_url() . $endpoint, $params);

  } // end do_request;

  /**
   * Build the API params from an array.
   *
   * @param  array  $data
   * @param  string $prefix
   * @return string
   */
  protected function build_api_params($data, $prefix = null) {

    if (!is_array($data)) {

      return $data;

    } // end if;

    $params = array();

    foreach ($data as $key => $value) {

      if (is_null($value)) {

        continue;

      } // end if;

      if ($prefix && $key && !is_int($key)) {

        $key = $prefix . '[' . $key . ']';

      } elseif ($prefix) {

        $key = $prefix . '[]';

      } // end if;

      if (is_array($value)) {

        $params[] = $this->build_api_params($value, $key);

      } else {

        $params[] = $key . '=' . urlencode($value);

      } // end if;

    } // end if;

    return implode('&', $params);

  } // end build_api_params;

  /**
   * Value in cents.
   *
   * @param  float $value
   * @return int
   */
  public function get_cents($value) {

    return number_format($value, 2, '', '');

  } // end get_cents;

  /**
   * Get phone number.
   *
   * @param  WC_Order $order WooCommerce Order.
   * @return string
   */
  protected function get_phone_number($order) {

    $phone_number = $this->only_numbers($order->get_billing_phone());

    return array(
      'area_code' => substr($phone_number, 0, 2),
      'number'    => substr($phone_number, 2)
    );

  } // end get_phone_number;

  /**
   * Get CPF or CNPJ.
   *
   * @param  WC_Order $order WooCommerce Order.
   * @return string
   */
  protected function get_cpf_cnpj($order) {

    $wcbcf_settings = get_option('wcbcf_settings');

    $person_type = intval($wcbcf_settings['person_type']);

    if (0 !== $person_type) {

      if ((1 === $person_type && 1 === intval($order->get_meta('_billing_persontype'))) || 2 === $person_type) {

        return $this->only_numbers($order->get_meta('_billing_cpf'));

      } // end if;

      if ( ( 1 === $person_type && 2 === intval( $order->get_meta( '_billing_persontype' ) ) ) || 3 === $person_type ) {

        return $this->only_numbers($order->get_meta('_billing_cnpj'));

      } // end if;

    } // end if;

    return '';

  } // end get_cpf_cnpj;

  /**
   * Check if the customer is a "company".
   *
   * @param  WC_Order $order WooCommerce Order.
   * @return bool
   */
  protected function is_a_company($order) {

    $wcbcf_settings = get_option('wcbcf_settings');

    $person_type = intval($wcbcf_settings['person_type']);

    if (($person_type === 1 && intval($order->get_meta('_billing_persontype')) === 2) || $person_type === 3) {

      return true;

    } // end if;

    return false;

  } // end is_a_company;

  /**
   * Get the invoice due date.
   *
   * @return string Invoice due date.
   */
  protected function get_invoice_due_date() {

    $days = 1;

    if ($this->method === 'bank_slip') {

      $days = intval($this->gateway->deadline);

    } // end if;

    return date('Y-m-d', strtotime('+' . $days . ' day'));

  } // end get_invoice_due_date;

  /**
   * Get the invoice data.
   *
   * @param  object|WC_Order $order WooCommerce Order.
   * @return array Invoice data organized.
   */
  protected function get_invoice_data($order) {

    $items = array();

    $phone_number = $this->get_phone_number($order);

    $payable_with = str_replace('-', '_', $this->method);

    $data = array(
      'email'                   => $order->get_billing_email(),
      'due_date'                => $this->get_invoice_due_date(),
      'ensure_workday_due_date' => false,
      'return_url'              => $this->gateway->get_return_url($order),
      'expired_url'             => str_replace('&#038;', '&', $order->get_cancel_order_url()),
      'notification_url'        => $this->get_wc_request_url(),
      'ignore_due_email'        => $this->gateway->ignore_due_email == 'yes' ? true : false,
      'payable_with'            => $payable_with,
      'custom_variables'        => array(
        array(
          'name'  => 'order_id',
          'value' => $order->get_id()
        )
      ),
      'payer'      => array(
        'name'         => $order->get_formatted_billing_full_name(),
        'phone_prefix' => $phone_number['area_code'],
        'phone'        => $phone_number['number'],
        'email'        => $order->get_billing_email(),
        'address'      => array(
          'street'   => $order->get_billing_address_1(),
          'number'   => $order->get_meta( '_billing_number' ),
          'city'     => $order->get_billing_city(),
          'state'    => $order->get_billing_state(),
          'country'  => isset( WC()->countries->countries[ $order->get_billing_country() ] ) ? WC()->countries->countries[ $order->get_billing_country() ] : $order->get_billing_country(),
          'zip_code' => $this->only_numbers( $order->get_billing_postcode() )
        )
      ),
    );

    if ($cpf_cnpj = $this->get_cpf_cnpj($order)) {

      $data['payer']['cpf_cnpj'] = $cpf_cnpj;

    } // end if;

    if ($this->is_a_company($order)) {

      $data['payer']['name'] = $order->get_billing_company();

    } // end if;

    if (!empty($order->get_meta('_billing_neighborhood'))) {

      $data['payer']['address']['district'] = $order->get_meta('_billing_neighborhood');

    } // end if;

    /**
     * Force only one item.
     */
    if ($this->gateway->send_only_total == 'yes') {

      $items[] = array(
        'description' => sprintf(__('Order %s', 'iugu-woocommerce'), $order->get_order_number()),
        'price_cents' => $this->get_order_total(),
        'quantity'    => 1
      );

    } else {

      /**
       * Products.
       */
      if (0 < count($order->get_items())) {

        foreach ($order->get_items() as $order_item) {

          if ($order_item['qty']) {

            $item_total = $this->get_cents($order->get_item_total($order_item, false));

            if (0 > $item_total) {

              continue;

            } // end if;

            $item_name = $order_item['name'];

            $item_meta = new WC_Order_Item_Product($order_item['item_meta']);

            if ( $meta = $item_meta->get_formatted_meta_data() ){

              $item_name .= ' - ' . $meta;

            } // end if ;

            $items[] = array(
              'description' => $item_name,
              'price_cents' => $item_total,
              'quantity'    => $order_item['qty']
            );

          } // end if;

        } // end foreach;

      } // end if;

      /**
       * Fees.
       */
      if (0 < count($order->get_fees())) {

        foreach ($order->get_fees() as $fee) {

          $fee_total = $this->get_cents($fee['line_total']);

          $items[] = array(
            'description' => $fee['name'],
            'price_cents' => $fee_total,
            'quantity'    => 1
          );

        } // end if;

      } // end if;

      /**
       * Taxes.
       */
      if (0 < count($order->get_taxes())) {

        foreach ($order->get_taxes() as $tax) {

          $tax_total = $this->get_cents($tax['tax_amount'] + $tax['shipping_tax_amount']);

          if (0 > $tax_total) {

            continue;

          } // end if;

          $items[] = array(
            'description' => $tax['label'],
            'price_cents' => $tax_total,
            'quantity'    => 1
          );

        } // end foreach;

      } // end if;

      /**
       * Shipping Cost.
       */
      $shipping_cost = $this->get_cents($order->get_shipping_total());

      if (0 < $shipping_cost) {

        $items[] = array(
          'description' => sprintf(__('Shipping via %s', 'iugu-woocommerce'), $order->get_shipping_method()),
          'price_cents' => $shipping_cost,
          'quantity'    => 1
        );

      } // end if;

      /**
       * Discount.
       *
       */
      if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.3', '<')) {
        /**
         * @param object|WC_Order $order
         */
        if (0 < $order->get_order_discount()) {

          $data['discount_cents'] = $this->get_cents($order->get_order_discount());

        } // end if;

      } // end if;

    } // end if;

    $data['items'] = $items;

    $data = apply_filters('iugu_woocommerce_invoice_data', $data);

    return $data;

  } // end get_invoice_data;

  /**
   * Get Invoice by invoice_id
   *
   * @param string $invoice_id
   * @return mixed
   */
  public function get_invoice_by_id($invoice_id) {

    $endpoint = 'invoices/' . $invoice_id . '/';

    $response = $this->do_request($endpoint, 'GET');

    if (is_object($response) && is_wp_error($response)) {

      if ($this->gateway->debug == 'yes') {

        $this->gateway->log->add( $this->gateway->id, 'WP_Error while trying to get the subscription: ' . $response->get_error_message());

      } // end if

      return array(
        'errors' => $response->get_error_message()
      );

    } elseif (200 == $response['response']['code'] && 'OK' == $response['response']['message']) {

      $body = json_decode($response['body'], true);

      if ($this->gateway->debug === 'yes') {

        $this->gateway->log->add($this->gateway->id, $body);

      } // end if;

      return $body;

    } // end if;

  } // end get_invoice_by_id;

  /**
   * Create an invoice.
   *
   * @param  WC_Order $order WooCommerce Order.
   * @return string Invoice ID.
   */
  protected function create_invoice($order) {

    $invoice_data = $this->get_invoice_data($order);

    if ('yes' == $this->gateway->debug) {

      $this->gateway->log->add($this->gateway->id, 'Creating an invoice on iugu for order ' . $order->get_order_number() . ' with the following data: ' . print_r($invoice_data, true));

    } // end if;

    $invoice_data = $this->build_api_params($invoice_data);

    $response = $this->do_request('invoices', 'POST', $invoice_data);

    if (is_object($response) && is_wp_error($response)) {

      if ('yes' == $this->gateway->debug) {

        $this->gateway->log->add( $this->gateway->id, 'WP_Error while trying to generate an invoice: ' . $response->get_error_message() );

      } // end if;

    } elseif (200 == $response['response']['code'] && 'OK' == $response['response']['message']) {

      if ('yes' == $this->gateway->debug) {

        $this->gateway->log->add($this->gateway->id, 'Invoice created successfully!');

      } // end if;

      $body = json_decode( $response['body'], true);

      return array(
        'id' => $body['id'],
      );

    } // end if;

    if ('yes' == $this->gateway->debug) {

      $this->gateway->log->add($this->gateway->id, 'Error while generating the invoice for order ' . $order->get_order_number() . ': ' . print_r($response, true));

    } // end if ;

  } // end create_invoice;

  /**
   * Get invoice status.
   *
   * @param  string $invoice_id
   * @return string Invoice status.
   */
  public function get_invoice_status($invoice_id) {

    $iugu_options = get_option('woocommerce_iugu-credit-card_settings');

    if (is_array($iugu_options) && isset($iugu_options['api_token'])) {

      $response = $this->do_request('invoices/' . $invoice_id, 'GET', array(), array(), $iugu_options['api_token']);

      $invoice = json_decode($response['body'], true);

      if (!isset($invoice['errors'])) {

        return sanitize_text_field($invoice['status']);

      } // end if;

    } // end if;

  } // end get_invoice_status;

  /**
   * Get charge data.
   *
   * @param  WC_Order $order WooCommerce Order.
   * @param  array    $posted $_POST data.
   * @return array    Charge data.
   */
  protected function get_charge_data($order, $posted = array()) {

    $invoice = $this->create_invoice($order);

    if (!isset($invoice['id'])) {

      if ('yes' == $this->gateway->debug) {

        $this->gateway->log->add($this->gateway->id, 'Error while getting the charge data for order ' . $order->get_order_number() . ': Missing the invoice ID.');

      } // end if;

      return array(
        'response' => $invoice['response'],
      );

    } // end if;

    $data = array(
      'invoice_id' => $invoice['id'],
    );

    /**
     * Credit Card.
     */
    if ('credit-card' == $this->method) {

      if (isset($posted['iugu_token'])) {

        /**
         * Credit card token.
         */
        $data['token'] = sanitize_text_field($posted['iugu_token']);

        /**
         * Installments.
         */
        if (isset($posted['iugu_card_installments']) && 1 < $posted['iugu_card_installments']) {

          $data['months'] = absint($posted['iugu_card_installments']);

        } // end if;

      } // end if;

      /**
       * Payment method ID.
       */
      if (isset($posted['customer_payment_method_id'])) {

        $customer_payment_method_id = WC_Payment_Tokens::get($posted['customer_payment_method_id']);

        $data['customer_payment_method_id'] = $customer_payment_method_id->get_token();

      } // end if;

    } // end if;

    /**
     * Bank Slip.
     */
    if ('bank-slip' == $this->method)  {

      $data['method'] = 'bank_slip';

    } // end if;

    /**
     * Bank Slip.
     */
    if ('pix' == $this->method)  {

      $data['method'] = 'pix';

    } // end if;

    $data = apply_filters('iugu_woocommerce_charge_data', $data);

    return $data;

  } // end get_charge_data;

  /**
   * Create Charge.
   *
   * @param  WC_Order $order
   * @param  array    $posted
   * @return array
   */
  public function create_charge($order, $posted = array()) {

    if ('yes' == $this->gateway->debug) {

      $this->gateway->log->add($this->gateway->id, 'Doing charge for order ' . $order->get_order_number() . '...');

    } // end if;

    if ($this->method === 'pix') {

      $endpoint = 'invoices/';

      $charge_data = $this->get_invoice_data($order, $posted);

    } else {

      $endpoint = 'charge';

      $charge_data = $this->get_charge_data($order, $posted);

      if (!isset($charge_data['invoice_id'])) {

        return $charge_data;

      } // end if;

    } // end if;

    $charge_data = $this->build_api_params($charge_data);

    $response = $this->do_request($endpoint, 'POST', $charge_data);

    if (is_object($response) && is_wp_error($response)) {

      if ( 'yes' == $this->gateway->debug ) {

        $this->gateway->log->add($this->gateway->id, 'WP_Error while trying to do a charge: ' . $response->get_error_message());

      } // end if;

    } elseif (isset($response['body']) && !empty($response['body'])) {

      $charge = json_decode($response['body'], true);

      if (isset($charge['errors']) && $charge['errors']) {

        if ('yes' == $this->gateway->debug) {

          $this->gateway->log->add($this->gateway->id, 'Errors: ' . print_r($charge['errors'], TRUE));

        } // end if;

        return $charge;

      } // end if;

      if ('yes' == $this->gateway->debug && isset($charge['success'])) {

        $this->gateway->log->add($this->gateway->id, 'Charge created successfully!');

      } // end if;

      return $charge;

    } // end if;

    if ( 'yes' == $this->gateway->debug ) {

      $this->gateway->log->add($this->gateway->id, 'Error while doing the charge for order ' . $order->get_order_number() . ': ' . print_r($response, true));

    } // end if;

    return array( 'errors' => array(__('An error has occurred while processing your payment. Please, try again or contact us for assistance.', 'iugu-woocommerce')));

  } // end create_charge;

  /**
   * Creates a Iugu Subscription.
   *
   * @since 2.20
   *
   * @param WC_Order $order WooCommerce Order.
   * @param object $plan Iugu plan.
   * @param string $customer_id Iugu customer ID.
   * @return array API response.
   */
  public function create_iugu_subscription($order, $plan, $customer_id) {

    if ('bank-slip' === $this->method) {

      $payable_with = 'bank_slip';

      $expires_at = date('d-m-Y', current_time('timestamp', 1));

      $only_on_charge_success = false;

    } // end if;

    if ('credit-card' == $this->method) {

      $payable_with = 'credit_card';

      $expires_at = null;

      $only_on_charge_success = true;

    } // end if;

    if ('pix' == $this->method ) {

      $payable_with = 'pix';

      $expires_at = date('d-m-Y', current_time('timestamp', 1));

      $only_on_charge_success = false;

    } // end if;

    $create_subscription = array(
      "plan_identifier"        => $plan['identifier'],
      "customer_id"            => $customer_id,
      "expires_at"             => $expires_at,
      "only_on_charge_success" => $only_on_charge_success,
      "ignore_due_email"       => "false",
      "payable_with"           => $payable_with,
      "credits_based"          => "false",
      "subitems" => array(),
         "two_step"                   => "false",
         "suspend_on_invoice_expired" => "true"
    );

    $create_subscription = $this->order_has_subitems($order, $create_subscription);

    $subscription_data = $this->build_api_params($create_subscription);

    /**
     * Requesting subscription creation.
     */
    $response = $this->do_request('subscriptions', 'POST', $subscription_data);

    $body = json_decode($response['body'], true);

    return $body;

  } // end create_subscription;

  /**
   * Adds subitems to the subscription array, if the order has them.
   *
   * @since 2.20
   *
   * @param object|WC_Order $order WooCommerce Order.
   * @param array $create_subscription Iugu subscription data.
   * @return array Subscription data.
   */
  public function order_has_subitems($order , $create_subscription){

    foreach ($order->get_items() as $item_id => $item_values) {

      $item_meta = get_post_meta($item_values->get_product_id());

      if (!$item_meta['_iugu_plan_id']) {

        $item = array(
          "description" => $item_values->get_name(),
          "price_cents" => $this->get_cents($item_values->get_price()),
          "quantity"    => $item_values->get_quantity(),
          "recurrent"   => "false"
        );

        array_push($create_subscription['subitems'], $item);

      } else {

        $product_subscription = wc_get_product($item_values->get_product_id());

        if ($product_subscription) {

          $signup_fee = WC_Subscriptions_Product::get_sign_up_fee($product_subscription);

          if ($signup_fee) {

            $item = array(
              "description" => __('Sign up fee', 'iugu-woocommerce'),
              "price_cents" => $this->get_cents($signup_fee),
              "quantity"    => 1,
              "recurrent"   => "false"
            );

            array_push($create_subscription['subitems'], $item);

          } // end if;

        } // end if;

      } // end if;

    } // end foreach;

    foreach($order->get_items('fee') as $item_id => $item_fee) {

      $fee_name = $item_fee->get_name();

      $fee_total = $this->get_cents($item_fee->get_total());

      $item = array(
        "description" => $fee_name,
        "price_cents" => -1 * abs($fee_total),
        "quantity"    => 1,
        "recurrent"   => "false"
      );

      $maybe_iugu_handle_subscriptions = get_option('enable_iugu_handle_subscriptions');

      $iugu_subscription_discount_type = get_option('iugu_subscription_discount_type');

      if ($maybe_iugu_handle_subscriptions === 'yes') {

        if ($iugu_subscription_discount_type === 'persistent') {

          $item['recurrent'] = true;

        } // end

      } // end if;

      array_push($create_subscription['subitems'], $item);

    } // foreach;

    return $create_subscription;

  } // end order_has_subitems;

  /**
   * Create customer in iugu API.
   *
   * @param  WC_Order $order Order data.
   * @return string Customer ID.
   */
  protected function create_customer($order) {

    if ('yes' == $this->gateway->debug) {

      $this->gateway->log->add($this->gateway->id, 'Creating customer...');

    } // end if;

    $data = array(
      'email'           => $order->get_billing_email(),
      'name'            => trim($order->get_formatted_billing_full_name()),
      'set_as_default'	=> true,
      'country'         => 'BRL'
    );

    if ($cpf_cnpj = $this->get_cpf_cnpj($order)) {

      $data['cpf_cnpj'] = $cpf_cnpj;

    } // end if;

    if ($phone = $this->get_phone_number($order)) {

      $data['phone'] = $phone['number'];

      $data['phone_prefix'] = '0' . $phone['area_code'];

    } // end if;

    if ($street = $order->get_billing_address_1()) {

      $data['street'] = $street;

    } // end if;

    if ($billing_number = $order->get_meta('_billing_number')) {

      $data['number'] = $billing_number;

    } // end if;

    if ($city = $order->get_billing_city()) {

      $data['city'] = $city;

    } // end if;

    if ($state = $order->get_billing_state()) {

      $data['state'] = $state;

    } // end if;

    if ($zip_code = $this->only_numbers($order->get_billing_postcode())) {

      $data['zip_code'] = $zip_code;

    } // end if;

    $data = apply_filters('iugu_woocommerce_customer_data', $data, $order);

    $customer_data = $this->build_api_params($data);

    $response = $this->do_request('customers', 'POST', $customer_data);

    $body = json_decode($response['body'], true);

    if (isset($body['id']) && !empty($body['id'])) {

      return $body['id'];

    } // end if;

  } // end create_customer;

  /**
   * Set customer default payment method in iugu API.
   *
   * @param  WC_Order $order WooCommerce Order.
   * @param  string $payment_id.
   * @return void.
   */
  public function set_default_payment_method($order, $payment_id) {

    $customer_id = get_user_meta($order->get_user_id(), '_iugu_customer_id', true);

    $data = $this->build_api_params(
      array(
        'default_payment_method_id' => $payment_id
      )
    );

    $response = $this->do_request('customers/' . $customer_id, 'PUT', $data);

    $body = json_decode($response['body'], true);

    return $body;

  } // end set_default_payment_method;

  /**
   * Remove customer payment method in iugu API.
   *
   * @param  WC_Order $order WooCommerce Order.
   * @param  string $payment_id.
   * @return void.
   */
  public function remove_payment_method($order, $payment_id) {

    $customer_id = get_user_meta($order->get_user_id(), '_iugu_customer_id', true);

    $response = $this->do_request('customers/' . $customer_id . '/payment_methods/' . $payment_id, 'DELETE', array());

    if (is_object($response) && is_wp_error($response)) {

      if ('yes' == $this->gateway->debug) {

        $this->gateway->log->add($this->gateway->id, 'WP_Error while trying to remove payment method: ' . $response->get_error_message());

      } // end if;

    } elseif (isset($response['body']) && !empty($response['body'])) {

      if ('yes' == $this->gateway->debug && isset($customer['id'])) {

        $this->gateway->log->add($this->gateway->id, 'Payment method removed successfully!');

      } // end if;

    } // end if;

  } // end remove_payment_method;

  /**
   * Get customer ID.
   *
   * @param  WC_Order $order Order data.
   * @return string Customer ID.
   */
  public function get_customer_id($order) {

    $user_id = $order->get_user_id();

    /**
     * Try get a saved customer ID.
     */
    if (0 < $user_id) {

      $customer_id = get_user_meta($user_id, '_iugu_customer_id', true);

      if ($customer_id) {

        return $customer_id;

      } // end if;

    } // end if;

    /**
     * Create customer in iugu.
     */
    $customer_id = $this->create_customer($order);

    /**
     * Save the customer ID.
     */
    if (0 < $user_id) {

      update_user_meta($user_id, '_iugu_customer_id', $customer_id);

    } // end if;

    return $customer_id;

  } // end get_customer_id;

  /**
   * Create a custom payment method.
   *
   * @param  WC_Order $order WooCommerce Order.
   * @param  string   $card_token   Credit card token.
   * @param  string   $customer_id
    * @return string   Payment method ID.
   */
  public function create_customer_payment_method($order = null, $card_token, $customer_id = '') {

    if (!$order && !$customer_id) {

      return;

    } // end if;

    if ($order) {

      $customer_id = $this->get_customer_id($order);

      $description = sprintf(__('Payment method created for order %s', 'iugu-woocommerce'), $order->get_order_number());

    } else {

      $description = __('Payment method created', 'iugu-woocommerce');

    } // end if;

    $data = array(
      'customer_id' => $customer_id,
      'description' => $description,
      'token'       => $card_token
    );

    if ($order) {

      $data = apply_filters('iugu_woocommerce_customer_payment_method_data', $data, $customer_id, $order);

    } // end if;

    $payment_data = $this->build_api_params($data);

    $response = $this->do_request('customers/' . $customer_id . '/payment_methods', 'POST', $payment_data);

    $body = json_decode($response['body'], true);

    if (isset($body)) {

      return $body;

    } // end if;

  } // end create_customer_payment_method;

  /**
   * Set the Payment Token in WooCommerce for the current customer.
   *
   * @param array $token_info
   * @return void
   */
  public function set_wc_payment_method($token_info) {

    $token = new WC_Payment_Token_CC();

    $token->set_token($token_info['id']);

    $token->set_gateway_id($this->gateway->id);

    $token->set_card_type($token_info['data']['brand']);

    $token->set_last4(str_replace('XXXX-XXXX-XXXX-', '', $token_info['data']['display_number']));

    $token->set_expiry_month($token_info['data']['month']);

    $token->set_expiry_year($token_info['data']['year']);

    $token->set_user_id(get_current_user_id());

    $saved_token = $token->save();

  } // end set_wc_payment_method;

  /**
   * Get the customer payment methods.
   *
   * @param  string $customer_id Iugu Customer ID.
   * @return array Customer payment methods.
  */
  public function get_payment_methods($customer_id) {

    return WC_Payment_Tokens::get_customer_tokens(get_current_user_id());

  } // end get_payment_methods;

  /**
   * Get the plan id from product meta.
   *
   * @param  string $order_id
   * @return array
  */
  public function get_product_plan_id($order_id) {

    $order 	= new WC_Order($order_id);

    foreach ($order->get_items() as $item_id => $item_values) {

      /**
       * Product_id
       */
      $product_id = $item_values->get_product_id();

      /**
       * OR the Product id from the item data
       */
      $iugu_plan_id = get_post_meta($product_id, '_iugu_plan_id');

    } // end foreach;

    if (isset($iugu_plan_id[0])) {

      return $iugu_plan_id[0];

    } // end if;

  } // end get_product_plan_id;

  /**
   * Get the plan params from Iugu API
   *
   * @since 2.20
   *
   * @param  string $plan_id Iugu Plan ID.
   * @return array Plan information.
  */
  public function get_iugu_plan($plan_id) {

    $endpoint = 'plans/' . $plan_id . '/';

    $response = $this->do_request($endpoint, 'GET');

    if (is_object($response) && is_wp_error($response)) {

      if ('yes' == $this->gateway->debug) {

        $this->gateway->log->add($this->gateway->id, 'WP_Error while trying create a customer payment method: ' . $response->get_error_message());

      } // end if;

    } elseif (isset($response['body']) && !empty($response['body'])) {

      $body = json_decode($response['body'], true);

      if ('yes' == $this->gateway->debug && isset($payment_method['id'])) {

        $this->gateway->log->add($this->gateway->id, $body);

      } // end if;

      return $body;

    } // end if;

  } // end get_iugu_plan;

  /**
   * Create a Iugu plan.
   *
   * @since 2.20
   *
   * @param  array $params Plan params.
   * @param  string $plan_id Iugu Plan ID
   * @return array Iugu Plan information.
  */
  public function create_iugu_plan($params, $plan_id = '') {

    $endpoint = 'plans/';

    $method = 'POST';

    if ($plan_id) {

      $endpoint = $endpoint . '/' . $plan_id . '/';

      $method = 'PUT';

    } // end if;

    $iugu_options = get_option('woocommerce_iugu-credit-card_settings');

    if (is_array($iugu_options) && isset($iugu_options['api_token'])) {

      $params = $this->build_api_params($params);

      $response = $this->do_request($endpoint, $method, $params, array(), $iugu_options['api_token']);

      $body = json_decode($response['body'], true);

      return $body;

    } // end if;

  } // end create_iugu_plan;

  /**
   * Update a iugu plan.
   *
   * @since 2.20
   *
   * @param array $params
   * @param string $plan_id
   * @return mixed
   */
  public function update_iugu_plan($params, $plan_id = '') {

    $endpoint = 'plans/' . $plan_id . '/';

    $iugu_options = get_option('woocommerce_iugu-bank-slip_settings');

    if (!$iugu_options) {

      $iugu_options = get_option('woocommerce_iugu-credit-card_settings');

    } // end if

    if (!$iugu_options) {

      $iugu_options = get_option('woocommerce_iugu-pix_settings');

    } // end if

    if (is_array($iugu_options) && isset($iugu_options['api_token'])) {

      $params = $this->build_api_params($params);

      $response = $this->do_request($endpoint, 'PUT', $params, array(), $iugu_options['api_token']);

      $body = json_decode($response['body'], true);

      return $body;

    } // end if;

  } // end update_iugu_plan;

  /**
   * Delete a Iugu plan.
   *
   * @since 2.20
   *
   * @param  string $plan_id Iugu Plan ID
   * @return void
  */
  public function delete_iugu_plan($plan_id) {

    $endpoint = 'plans/' . $plan_id . '/';

    $iugu_options = get_option('woocommerce_iugu-bank-slip_settings');

    if (is_array($iugu_options) && isset($iugu_options['api_token'])) {

      $response = $this->do_request($endpoint, 'DELETE', array(), array(), $iugu_options['api_token']);

      if (is_object($response) && is_wp_error($response)) {

        $body = json_decode($response['body'], true);

        return $body;

      } // end if;

    } // end if;

  } // end delete_iugu_plan;

  /**
   * Process Iugu payment.
   *
   * @param int $order_id The WC Order ID
   * @param string $customer_payment_id The customer payment method id, If has one
   * @return void
   */
  public function process_payment($order_id) {

    $order = new WC_Order($order_id);

    $charge = $this->create_charge($order, $_POST);

    if (isset($charge['errors']) && $charge['errors']) {

      $this->add_error($charge['errors']);

      return;

    } // end if;

    /**
     * Save transaction data.
     */
    if ('bank-slip' == $this->method) {

      $payment_data = array_map(
        'sanitize_text_field',
        array(
          'pdf' => $charge['pdf']
        )
      );


    if (isset($charge['invoice_id'])) {

      update_post_meta($order->get_id(), '_transaction_id', sanitize_text_field($charge['invoice_id']));

    } // end if;

      update_post_meta($order->get_id(), __('iugu bank slip URL', 'iugu-woocommerce' ), $payment_data['pdf']);

    } elseif ($this->method == 'pix') {

      $payment_data = array_map(
        'sanitize_text_field',
        array(
          'qrcode'      => $charge['pix']['qrcode'],
          'qrcode_text' => $charge['pix']['qrcode_text'],
        )
      );

    } else {

      $payment_data = array_map(
        'sanitize_text_field',
        array(
          'installments' => isset( $_POST['iugu_card_installments'] ) ? sanitize_text_field( $_POST['iugu_card_installments'] ) : '1'
        )
      );

    } // end if;

    update_post_meta($order->get_id(), '_iugu_wc_transaction_data', $payment_data);

    if (isset($charge['id'])) {

      update_post_meta($order->get_id(), '_transaction_id', sanitize_text_field($charge['id']));

    } // end if;

    /**
     * Save only in old versions.
     */
    if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.1.12', '<=')) {

      update_post_meta($order->get_id(), __('iugu transaction details', 'iugu-woocommerce' ), 'https://iugu.com/a/invoices/' . sanitize_text_field($charge['id']));

    } // end if;

    $this->empty_card();

    if ('bank-slip' == $this->method) {

      $order->update_status('on-hold', __('iugu: The customer generated a bank slip. Awaiting payment confirmation.', 'iugu-woocommerce'));

    } elseif ('pix' == $this->method) {

      $order->update_status('on-hold', __( 'iugu: The customer generated a pix payment. Awaiting payment confirmation.', 'iugu-woocommerce'));

      return array(
        'result'   => 'success',
        'redirect' => $this->gateway->get_return_url($order),
        'success' => $charge
      );

    } else {

      if (true == $charge['success']) {

        $order->add_order_note(__('iugu: Invoice paid successfully by credit card.', 'iugu-woocommerce'));

        $order->payment_complete();

      } else {

        $order->update_status('failed', __('iugu: Credit card declined.', 'iugu-woocommerce'));

      } // end if;

    } // end if;

    if (isset($charge['success'])) {

      return array(
        'result'   => 'success',
        'redirect' => $this->gateway->get_return_url($order),
        'success' => $charge['success']
      );

    } // end if;

  } // end process_payment;

  /**
   * Update order status.
   *
   * @param int    $order_id
   * @param string $invoice_status
   * @param bool   $is_subscription
   * @return bool
   */
  protected function update_order_status($order_id, $invoice_status, $is_subscription = false) {

    $order = wc_get_order($order_id);

    $invoice_status = strtolower($invoice_status);

    $order_status   = $order->get_status();

    $order_updated = true;

    if (isset($this->gateway->debug) && 'yes' == $this->gateway->debug) {

      $this->gateway->log->add($this->gateway->id, 'iugu payment status for order ' . $order->get_order_number() . ' is now: ' . $invoice_status);

    } // end if;

    switch ($invoice_status) {

      case 'pending' :

        if (!in_array($order_status, array('on-hold', 'processing', 'completed'))) {

          if ('bank-slip' == $this->method) {

            $order->update_status('on-hold', __( 'iugu: The customer generated a bank slip. Awaiting payment confirmation.', 'iugu-woocommerce' ) );

          } elseif('pix' == $this->method) {

            $order->update_status('on-hold', __('iugu: The customer generated a pix payment. Awaiting payment confirmation.', 'iugu-woocommerce'));

          } else {

            $order->update_status('on-hold', __('iugu: Invoice paid by credit card. Waiting for the acquirer confirmation.', 'iugu-woocommerce'));

          } // end if;

          $order_updated = true;

        } // end if;

        break;
      case 'paid' :
        if (!in_array($order_status, array('processing', 'completed'))) {

          $order->add_order_note(__( 'iugu: Invoice paid successfully.', 'iugu-woocommerce'));

          $subscriptions = false;

          if (function_exists('wcs_get_subscriptions_for_order')) {

            $subscriptions = wcs_get_subscriptions_for_order($order);

            if (!empty($subscriptions)) {

              foreach ($subscriptions as $subscription) {

                $subscription->update_status('active');

              } // end foreach;

              $order->update_status('processing');

            } else {

              /**
               * Changing the order for processing and reduces the stock.
               */
              $order->payment_complete();

            } // end if;

          } else {

            /**
             * Changing the order for processing and reduces the stock.
             */
            $order->payment_complete();

          } // end if;

          $order_updated = true;

        } // end if;

        break;
      case 'canceled' :
        $order->update_status('cancelled', __('iugu: Invoice canceled.', 'iugu-woocommerce'));

        $order_updated = true;

        break;
      case 'partially_paid' :
        $order->update_status('on-hold', __( 'iugu: Invoice partially paid.', 'iugu-woocommerce'));

        $order_updated = true;

        break;
      case 'refunded' :
        $order->update_status('refunded', __( 'iugu: Invoice refunded.', 'iugu-woocommerce'));

        $this->send_email(
          sprintf( __('Invoice for order %s was refunded', 'iugu-woocommerce'), $order->get_order_number()),
          __('Invoice refunded', 'iugu-woocommerce'),
          sprintf( __( 'Order %s has been marked as refunded by iugu.', 'iugu-woocommerce'), $order->get_order_number())
        );

        $order_updated = true;

        break;
      case 'expired' :
        $order->update_status('failed', __('iugu: Invoice expired.', 'iugu-woocommerce'));

        $order_updated = true;

        break;

      default :
        // No action.
        break;

    } // end switch;

    /**
     * Allow custom actions when update the order status.
     */
    do_action('iugu_woocommerce_update_order_status', $order, $invoice_status, $order_updated );

    return $order_updated;

  } // end update_order_status;

  /**
   * Payment notification handler.
   *
   * @return void.
   */
  public function notification_handler() {

    @ob_clean();

    if (isset($_REQUEST['event'])) {

      if ($_REQUEST['event'] === 'invoice.status_changed') {

        global $wpdb;

        header('HTTP/1.1 200 OK');

        $invoice_id = sanitize_text_field($_REQUEST['data']['id']);

        $invoice_status = $this->get_invoice_status($invoice_id);

        /**
         * Find order id by the invoice_id meta.
         */
        $order_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_transaction_id' AND meta_value = '%s'", $invoice_id));

        $order_id = intval($order_id);

        if ($order_id) {

          $maybe_wcs_subscription = false;

          $maybe_wcs_subscription = get_option('enable_iugu_handle_subscriptions', 'no');

          if ($maybe_wcs_subscription === 'yes') {

            $this->update_order_status($order_id, $invoice_status, true);

            exit();

          } else {

            $this->update_order_status($order_id, $invoice_status);

            exit();

          } // end if;

        } // end if;

      } // end if;

    } // end if;

    wp_die( __('The request failed!', 'iugu-woocommerce'), __('The request failed!', 'iugu-woocommerce'), array('response' => 200));

  } // end notification_handler;

  /**
   * Refund order.
   *
   * @param  string $order WooCommerce Order ID.
   * @param  string $amount Amount to refund.
   * @return void.
   */
  public function refund_order($order_id, $amount) {

    if (empty($order_id)) {

      return false;

    } // end if;

    $order = wc_get_order($order_id);

    $total = $order->get_total();

    if ($total != $amount) {

      throw new Exception( __("Can't do partial refunds", 'iugu-woocommerce'));

    } // end if;

    $transaction_id = get_post_meta($order_id, '_transaction_id', true);

    $response = $this->do_request('invoices/' . $transaction_id . '/refund', 'POST', array());

    if (is_object($response) && is_wp_error($response)) {

      if ('yes' == $this->gateway->debug) {

        $this->gateway->log->add($this->gateway->id, 'WP_Error while trying to refund order' . $order_id . ': ' . $response->get_error_message());

      } // end if;

      return $response;

    } elseif (isset($response['body'] ) && !empty($response['body'])) {

      if ('yes' == $this->gateway->debug && isset($response['body']['status']) && $response['body']['status'] == "refunded") {

        $this->gateway->log->add($this->gateway->id, 'Order refunded successfully!');

      } // end if;

      return true;

    } // end if;

  } // end refund_order;

  /**
   * Convert iugu API parameters from the most common error responses
   * to make them more human-readabale and match fields' names
   * on the checkout page if necessary.
   *
   * @param string $param String or array to conivert.
   * @return string Converted params.
  */
  protected function convert_API_param($param) {

    $param = end(explode('.', $param));

    $map = array(
      'email'    => __('Email address', 'iugu-woocommerce' ),
      'cpf_cnpj' => 'CPF/CNPJ',
      'street'   => __('Street address', 'iugu-woocommerce' ),
      'district' => __('Neighborhood', 'iugu-woocommerce' ),
      'city'     => __('Town / City', 'iugu-woocommerce' ),
      'state'    => __('State / County', 'iugu-woocommerce' ),
      'zip_code' => __('Postcode / ZIP', 'iugu-woocommerce' )
    );

    if (array_key_exists($param, $map)) {

      return $map[$param];

    } // end if;

    return ucfirst(str_replace('_', ' ', $param));

  } // end convert_API_param;

  /**
   * Creates a iugu webhook to receive all notifications.
   *
   * @return void
   */
  public function create_iugu_webhook() {

    $endpoint = 'web_hooks';

    $params = $this->build_api_params(array(
      'event' => 'all',
      'url'   => add_query_arg('action', 'wc_iugu_notification_handler', admin_url('admin-ajax.php')),
    ));

    $iugu_options = get_option('woocommerce_iugu-bank-slip_settings');

    if (is_array($iugu_options) && isset($iugu_options['api_token'])) {

      $params = $this->build_api_params($params);

      $response = $this->do_request($endpoint, 'POST', $params, array(), $iugu_options['api_token']);

      $body = json_decode($response['body'], true);

      return $body;

    } // end if;

  } // end create_iugu_webhook;

} // end WC_Iugu_API;
