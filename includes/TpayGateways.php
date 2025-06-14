<?php

namespace Tpay;

use Error;
use Exception;
use Tpay\Api\Client;
use Tpay\Api\Dtos\Channel;
use Tpay\Api\Transactions;
use Tpay\OpenApi\Api\TpayApi;
use WC_Order;
use WC_Payment_Gateway;

abstract class TpayGateways extends WC_Payment_Gateway
{
    public $has_terms_checkbox;
    public $id_seller;
    public $security_code;
    public $api_key;
    public $api_key_password;
    public $shipping;
    public $gateway_helper;
    public $request;
    public $payment_data;
    public $additional_payment_data;
    public $crc;
    public $site_domain;
    public $valid_mid = false;
    protected $tpay_numeric_id;
    protected $enable_for_shipping;
    protected static $banksGroupMicrocache = [true => null, false => null];
    protected static $tpayConnection;
    protected $cache;
    protected $config;

    /** @var Transactions */
    protected $transactions;

    /**
     * Setup general properties for the gateway.
     *
     * @param string     $id
     * @param null|mixed $tpay_numeric_id
     */
    public function __construct($id, $tpay_numeric_id = null)
    {
        $this->id = $id;
        $this->tpay_numeric_id = $tpay_numeric_id;
        $this->cache = new Helpers\Cache();

        $this->request = new Helpers\RequestHelper();
        $this->gateway_helper = new Helpers\GatewayHelper();
        $this->shipping = new Helpers\ShippingHelper();
        $this->transactions = new Transactions(new Client(), $this->cache);

        if (!isset(self::gateways_list()[$this->id])) {
            return;
        }

        $this->setup_properties($id);
        $this->init_form_fields();
        $this->init_settings();

        $this->icon = apply_filters('woocommerce_tpay_icon', plugin_dir_url(__FILE__).'../views/img/tpay.svg');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description', ' ');
        $this->enable_for_shipping = $this->get_option('enable_for_shipping', []);
        $this->site_domain = rtrim(preg_replace('/\?.*/', '', str_replace('http://', 'https://', home_url('/'))), '/');

        // Saving hook
        add_action('woocommerce_update_options_payment_gateways_'.$this->id, [$this, 'process_admin_options']);

        // Payment listener/API hook
        add_action('woocommerce_api_'.$this->id, [$this, 'gateway_ipn']);
        add_filter('woocommerce_available_payment_gateways', [$this, 'unset_gateway']);
    }

    /**
     * @param array<Channel> $list
     *
     * @return array<Channel>
     */
    public function filter_out_constraints(array $list): array
    {
        return array_filter($list, function (Channel $channel) {
            foreach ($channel->constraints as $constraint) {
                if ('amount' === $constraint->field) {
                    $cart_content_total = 0;

                    if (!is_admin() && WC()->cart) {
                        $cart_content_total = WC()->cart->get_cart_contents_total();
                    }

                    if ('min' == $constraint->type && $cart_content_total < $constraint->value) {
                        return false;
                    }

                    if ('max' == $constraint->type && $cart_content_total > $constraint->value) {
                        return false;
                    }
                }
            }

            return true;
        });
    }

    public function try_disable_gateway_by_cart_total($gatewayId = null)
    {
        $id = $this->id;

        if ($gatewayId) {
            $id = $gatewayId;
        }

        $values = $this->get_installments_values($id) + $this->get_generic_constraints();

        if (!isset($values[$id])) {
            return false;
        }

        if (empty($values[$id])) {
            return true;
        }

        $min = $values[$id]['min'];
        $max = $values[$id]['max'];

        $cart_content_total = $this->getCartTotal();

        return (bool) ($cart_content_total < $min || $cart_content_total > $max);
    }

    public function getCartTotal()
    {
        $cart_content_total = 0;

        if (!is_admin() && WC()->cart) {
            $cart_content_total = WC()->cart->get_cart_contents_total();
        }

        return $cart_content_total;
    }

    public function tpay_api()
    {
        if (null !== self::$tpayConnection) {
            return self::$tpayConnection;
        }

        $config = $this->get_config();
        $this->api_key = $config['api_key'];
        $this->api_key_password = $config['api_key_password'];
        $this->security_code = $config['security_code'];

        $this->set_id_seller($this->api_key);
        try {
            $isProd = 'sandbox' != tpayOption('global_tpay_environment');
            self::$tpayConnection = new TpayApi($this->api_key, $this->api_key_password, $isProd, 'read', null, buildInfo());

            $token = $this->cache->get(Client::TOKEN_CACHE_KEY);

            if ($token) {
                self::$tpayConnection->setCustomToken($token);
            } else {
                self::$tpayConnection->authorization();
                $this->cache->set(Client::TOKEN_CACHE_KEY, self::$tpayConnection->getToken());
            }

            return self::$tpayConnection;
        } catch (Exception $exception) {
            $this->gateway_helper->tpay_logger('Bramka Tpay nie została uruchomiona - brak danych lub dane niepoprawne');
            self::$tpayConnection = false; // microcache that tpay connection is unavailable
            if (is_admin() && strpos($exception->getMessage(), 'Authorization error')) {
                @add_settings_error('general', 'settings_updated', 'Tpay: Authorization error, wrong credentials.', 'error');
            }

            return false;
        }
    }

    public function set_id_seller($api_key = null)
    {
        if ($api_key) {
            if (false !== strpos($api_key, '-')) {
                $value = explode('-', $api_key);
                $this->id_seller = $value[0];
            }
        }
    }

    /** @return array */
    public static function gateways_list()
    {
        $list = [
            'tpaypbl' => [
                'name' => __('Tpay banks list', 'tpay'),
                'front_name' => __('Online payment by Tpay', 'tpay'),
                'default_description' => __('Choose payment method.', 'tpay'),
                'api' => TPAYPBL_ID,
                'group_id' => TPAYPBL,
            ],
            'tpayblik' => [
                'name' => __('Tpay Blik', 'tpay'),
                'front_name' => __('Online payment by BLIK', 'tpay'),
                'default_description' => '',
                'api' => TPAYBLIK_ID,
                'group_id' => TPAYBLIK,
            ],
            'pekaoinstallments' => [
                'name' => __('Pekao Installments', 'tpay'),
                'front_name' => __('Online payment by installments', 'tpay'),
                'default_description' => __('Installments', 'tpay'),
                'api' => TPAYPEKAOINSTALLMENTS_ID,
                'group_id' => TPAYPEKAOINSTALLMENTS,
            ],
            'tpaycc' => [
                'name' => __('Tpay Credit Card standard', 'tpay'),
                'front_name' => __('Online payment by Credit Card', 'tpay'),
                'default_description' => __('You will be redirected to tpay.com where you will enter your credit card details', 'tpay'),
                'api' => TPAYCC_ID,
                'group_id' => TPAYCC,
            ],
            'tpaysf' => [
                'name' => __('Tpay Credit Card SF', 'tpay'),
                'front_name' => __('Online payment by Credit Card SF', 'tpay'),
                'default_description' => '',
                'api' => TPAYSF_ID,
                'group_id' => TPAYSF,
            ],
        ];

        return apply_filters('tpay_generic_gateway_list', $list);
    }

    /**
     * @param string $field
     *
     * @return string
     */
    public function gateway_data($field)
    {
        $names = self::gateways_list();

        return $names[$this->id][$field];
    }

    public function init_form_fields()
    {
        $this->tpay_init_form_fields();
    }

    /**
     * @param bool $custom_order
     */
    public function tpay_init_form_fields($custom_order = false, $blik0 = false, $sf = false, $installments = false)
    {
        $this->form_fields = array_merge(
            $this->get_form_fields_basic(),
            $this->get_form_field_config(),
            $this->get_form_field_info(),
            $custom_order ? Tpay::get_form_custom_order() : [],
            $blik0 ? TpayBlik::get_form_blik0() : [],
            $sf ? TpaySF::get_form_sf() : [],
            $installments ? PekaoInstallments::get_form_installments() : [],
            [
                'notifications_url' => [
                    'title' => __('Your address for notifications', 'tpay'),
                    'type' => 'title',
                    'description' => add_query_arg('wc-api', 'Tpay', home_url('/')),
                ],
            ]
        );
    }

    /** @return false|string */
    public function tpay_get_option(array $key)
    {
        if (count($key) < 2) {
            return false;
        }

        $option = get_option($key[0]);

        if (false === is_array($option)) {
            return false;
        }

        return $option[$key[1]] ?? false;
    }

    /**
     * Check If The Gateway Is Available For Use.
     * Copy from COD module
     */
    public function is_available(): bool
    {
        $order = null;
        $is_order_processing = false;

        if (WC()->cart) {
            $is_order_processing = true;
        } elseif (is_page(wc_get_page_id('checkout')) && get_query_var('order-pay') > 0) {
            $order = wc_get_order(absint(get_query_var('order-pay')));
            $is_order_processing = true;
        }

        if (!empty($this->enable_for_shipping) && $is_order_processing) {
            $order_shipping_items = is_object($order) ? $order->get_shipping_methods() : false;
            $chosen_shipping_methods_session = WC()->session->get('chosen_shipping_methods');

            $products = WC()->cart->get_cart_contents();
            $virtualCart = true;

            foreach ($products as $product) {
                if (false === $product['data']->is_virtual()) {
                    $virtualCart = false;
                    break;
                }
            }

            if ($virtualCart) {
                return true;
            }

            if ($order_shipping_items) {
                $canonical_rate_ids = $this->shipping->get_canonical_order_shipping_item_rate_ids($order_shipping_items);
            } else {
                $canonical_rate_ids = $this->shipping->get_canonical_package_rate_ids($chosen_shipping_methods_session);
            }

            if (!count($this->shipping->get_matching_rates($canonical_rate_ids, $this->enable_for_shipping))) {
                return false;
            }
        }

        if (is_page(wc_get_page_id('checkout')) && get_query_var('order-pay') > 0 && !$this->tpay_api()) {
            return false;
        }

        return parent::is_available();
    }

    public function set_payment_data($order, $channelId)
    {
        $payer_data = $this->gateway_helper->payer_data($order, tpayOption('global_tax_id_meta_field_name'));
        $merchant_email = get_option('admin_email');

        if (tpayOption('global_merchant_email')) {
            $merchant_email = tpayOption('global_merchant_email');
        }

        $this->payment_data = [
            'description' => __('Order', 'tpay').' #'.$order->get_id(),
            'hiddenDescription' => $this->crc,
            'amount' => $order->get_total(),
            'pay' => [
                'channelId' => (int) $channelId,
                'method' => 'pay_by_link',
            ],
            'payer' => $payer_data,
            'lang' => tpay_lang(),
            'callbacks' => [
                'payerUrls' => [
                    'success' => $this->get_return_url($order),
                    'error' => wc_get_checkout_url(),
                ],
                'notification' => [
                    'url' => add_query_arg('wc-api', $this->gateway_data('api'), home_url('/')),
                    'email' => $merchant_email,
                ],
            ],
        ];
    }

    public function process_transaction(WC_Order $order)
    {
        try {
            $transaction = $this->tpay_api()->transactions()->createTransactionWithInstantRedirection($this->payment_data);
        } catch (Error $e) {
            $this->gateway_helper->tpay_logger($e->getMessage());

            return false;
        }

        if (isset($transaction['transactionPaymentUrl'])) {
            return $transaction;
        }

        return [
            'result' => 'error',
            'message' => __('Unable to create transaction', 'tpay'),
        ];
    }

    public function createCRC($order_id): string
    {
        return md5(time().$order_id);
    }

    /** @return array<Channel> */
    public function channels(): array
    {
        return $this->transactions->channels();
    }

    public function getBanksList($onlineOnly = false)
    {
        if (null !== self::$banksGroupMicrocache[$onlineOnly]) {
            return self::$banksGroupMicrocache[$onlineOnly];
        }

        $cacheKey = 'tpay_getBanksList-'.($onlineOnly ? 'online' : 'all');
        $cached = $this->cache->get($cacheKey);

        if ($cached) {
            self::$banksGroupMicrocache[$onlineOnly] = $cached;

            return $cached;
        }

        $api = $this->tpay_api();

        if (!$api) {
            return [];
        }
        $ttl = 1200;

        try {
            $result = $api->transactions()->getBankGroups($onlineOnly);
        } catch (Exception $exception) {
            $result = ['groups' => [], 'result' => 'fail'];
        }

        if (!isset($result['result']) || 'success' !== $result['result']) {
            $ttl = 60;
            $this->gateway_helper->tpay_logger('Nieudana próba pobrania listy banków');
        }

        self::$banksGroupMicrocache[$onlineOnly] = $result['groups'];
        $this->cache->set($cacheKey, $result['groups'], $ttl);

        return $result['groups'];
    }

    /**
     * @param int        $order_id
     * @param null|float $amount
     * @param string     $reason
     */
    public function process_refund($order_id, $amount = null, $reason = ''): bool
    {
        $order = wc_get_order($order_id);

        try {
            $result = $this->tpay_api()->transactions()->createRefundByTransactionId(['amount' => $amount], $order->get_transaction_id());

            if ('success' == $result['result'] && 'correct' == $result['status']) {
                return true;
            }

            $this->gateway_helper->tpay_logger("Nieudana próba zwrotu, odpowiedź z Tpay: \r\n".print_r($result, 1));

            return false;
        } catch (Exception $exception) {
            $this->gateway_helper->tpay_logger('Nie udało się utworzyć zwrotu');
            error_log("Can't process refund");

            return false;
        }
    }

    /** @throws */
    public function gateway_ipn()
    {
        $body = $_POST;
        Ipn\IpnContext::chooseStrategy($body);
        wp_die();
    }

    public function unset_gateway(array $gateways): array
    {
        if ('PLN' !== get_woocommerce_currency()
            || false === $this->payment_gateway_is_enabled()
            || true === $this->try_disable_gateway_by_cart_total()
            || false === $this->is_on_banks_list()
        ) {
            unset($gateways[$this->id]);
        }

        return $gateways;
    }

    public function payment_gateway_is_enabled(): bool
    {
        return 'yes' === $this->tpay_get_option(['woocommerce_'.$this->id.'_settings', 'enabled']);
    }

    protected function get_config(): array
    {
        if (!$this->config) {
            $this->config = (new Helpers\ConfigProvider())->get_config($this);
        }

        return $this->config;
    }

    /** @param string $id */
    protected function setup_properties($id)
    {
        $this->id = $id;
        $this->method_title = $this->gateway_data('name');
        $this->method_description = __('Official Tpay payment gateway for WooCommerce.', 'tpay');
        $this->has_fields = false;
        $this->supports = ['products', 'refunds'];
    }

    /** @return array */
    private function get_form_fields_basic()
    {
        return [
            'enabled' => [
                'title' => __('Launch payment', 'tpay'),
                'label' => __('Enable Tpay payment method', 'tpay'),
                'type' => 'checkbox',
                'description' => __(
                    'If you do not already have Tpay account, <a href="https://register.tpay.com/" target="_blank">please register</a>.',
                    'tpay'
                ),
                'default' => 'no',
            ],
            'title' => [
                'title' => __('Title', 'tpay'),
                'type' => 'text',
                'description' => __('Title of Tpay Payment Gateway that users sees on Checkout page.', 'tpay'),
                'default' => self::gateways_list()[$this->id]['front_name'],
                'desc_tip' => true,
            ],
            'use_global' => [
                'title' => __('Use global values', 'tpay'),
                'type' => 'checkbox',
                'label' => __('Use global values.', 'tpay'),
                'default' => 'yes',
                'description' => __('Function uses data from Tpay Settings', 'tpay'),
                'desc_tip' => true,
                'custom_attributes' => ['data-toggle-global' => '1'],
            ],
        ];
    }

    /** @return array */
    private function get_form_field_config()
    {
        $config = [];

        $fields = TpaySettings::tpay_fields();
        $settings = [];
        foreach ($fields as $field => $desc) {
            $settings[$field] = [
                'title' => $desc['label'],
                'type' => 'text',
                'description' => $desc['description'],
                'desc_tip' => true,
                'custom_attributes' => [
                    'data-global' => 'can-be-global',
                    'global-value' => $this->tpay_get_option(['tpay_settings_option_name', 'global_'.$field]),
                    'local-value' => $this->tpay_get_option(['woocommerce_'.$this->id.'_settings', $field]),
                ],
            ];
        }
        $config += $settings;

        return $config;
    }

    /** @return array */
    private function get_form_field_info()
    {
        return [
            'description' => [
                'title' => __('Description', 'tpay'),
                'type' => 'text',
                'description' => __('Description of Tpay Payment Gateway that users sees on Checkout page.', 'tpay'),
                'default' => self::gateways_list()[$this->id]['default_description'],
                'desc_tip' => true,
            ],

            'enable_for_shipping' => [
                'title' => __('Enable for shipping methods', 'tpay'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 400px;',
                'default' => '',
                'description' => __(
                    'If Tpay is only available for certain methods, set it up here. Leave blank to enable for all methods.',
                    'tpay'
                ),
                'options' => $this->shipping->shipping_methods(),
                'desc_tip' => true,
                'custom_attributes' => [
                    'data-placeholder' => __('Select shipping methods', 'tpay'),
                ],
            ],
        ];
    }

    private function is_on_banks_list(): bool
    {
        if (null === $this->tpay_numeric_id && count($this->getBanksList())) {
            return true;
        }

        foreach ($this->getBanksList() as $item) {
            if ($item['id'] == $this->tpay_numeric_id) {
                return true;
            }
        }

        return false;
    }

    private function get_installments_values(string $id): array
    {
        $valuesNames = [
            TPAYPEKAOINSTALLMENTS => TPAYPEKAOINSTALLMENTS_ID,
        ];
        $values = [
            TPAYPEKAOINSTALLMENTS_ID => [],
        ];

        if (!in_array($id, array_values($valuesNames))) {
            return $values;
        }

        $lookedId = array_flip($valuesNames)[$id];

        foreach ($this->channels() as $channel) {
            $groupId = !empty($channel->groups) ? $channel->groups[0]->id : null;

            if ($groupId === $lookedId && isset($channel->constraints[1]->value)) {
                $values[$valuesNames[$groupId]] = [
                    'min' => (float) $channel->constraints[0]->value,
                    'max' => (float) $channel->constraints[1]->value,
                ];
            }
        }

        return $values;
    }

    private function get_generic_constraints(): array
    {
        $genericPayments = tpayOption('global_generic_payments') ?? [];
        $values = [];
        foreach ($this->channels() as $channel) {
            if (in_array($channel->id, $genericPayments) && isset($channel->constraints[1]->value)) {
                $values['tpaygeneric-'.$channel->id] = [
                    'min' => (float) $channel->constraints[0]->value,
                    'max' => (float) $channel->constraints[1]->value,
                ];
            }
        }

        return $values;
    }
}
