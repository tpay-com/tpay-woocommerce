<?php

use Tpay\TpayBlik;
use Tpay\TpayGateways;

defined( 'ABSPATH' ) || exit();

/**
 * @return mixed|null
 */
function tpayOption(?string $optionName = null)
{
    $options = get_option('tpay_settings_option_name');

    if (empty($options)) {
        return null;
    }

    if ($optionName === null) {
        return $options;
    }

    return $options[$optionName] ?? null;
}

function tpay_add_checkout_fee_for_gateway()
{
    $chosen_gateway = WC()->session->get('chosen_payment_method');
    $type = tpayOption('global_enable_fee');

    if ($type == 'amount') {
        $fee = tpayOption('global_amount_fee');
    } else {
        $percentage = tpayOption('global_percentage_fee');
        $amount = WC()->cart->cart_contents_total + WC()->cart->shipping_total;
        $fee = $percentage / 100 * $amount;
    }

    $fee = round($fee, 2);

    if (array_key_exists($chosen_gateway, TpayGateways::gateways_list())) {
        WC()->cart->add_fee(__('Transaction fee', 'tpay'), $fee);
    }
}

function displayChildPluginNotice()
{
    echo sprintf(
        '<div class="error"><p>%s <a target="_blank" href="https://wordpress.org/plugins/woocommerce/">%s</a></p></div>',
        __('Tpay requires a WooCommerce plugin,', 'tpay'),
        __('download it', 'tpay')
    );
}

function childPluginHasParentPlugin()
{
    if (is_admin() && current_user_can('activate_plugins')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        add_action('admin_notices', 'displayChildPluginNotice');
        deactivate_plugins(plugin_basename(__FILE__));

        if (filter_input(INPUT_GET, 'activate')) {
            unset($_GET['activate']);
        }
    }
}

function enqueue_tpay_admin_assets()
{
//    wp_enqueue_script('tpay_admin_js', plugin_dir_url(__FILE__) . 'views/js/admin.min.js', [], TPAY_PLUGIN_VERSION);
    wp_enqueue_script('tpay_admin_js', plugin_dir_url(__FILE__) . 'views/js/admin.min.js', [], time());
    wp_enqueue_style('tpay_admin_css', plugin_dir_url(__FILE__) . 'views/css/admin.css', [], TPAY_PLUGIN_VERSION);
}

function buildInfo(): string
{
    return sprintf(
        'woocommerce:%s|wordpress:%s|tpay-woocommerce:%s|tpay-openapi-php:%s|PHP:%s',
        WC()->version,
        get_bloginfo('version'),
        TPAY_PLUGIN_VERSION,
        get_package_version(),
        phpversion()
    );
}

function enqueue_tpay_gateway_assets()
{
    wp_enqueue_script(
        'tpay_payment_js',
        plugin_dir_url(__FILE__) . 'views/js/jquery.payment.js',
        [],
        TPAY_PLUGIN_VERSION,
        true
    );
    wp_enqueue_script(
        'tpay_jsencrypt_js',
        plugin_dir_url(__FILE__) . 'views/js/jsencrypt.min.js',
        [],
        TPAY_PLUGIN_VERSION,
        true
    );
    wp_enqueue_script(
        'tpay_sr_js',
        plugin_dir_url(__FILE__) . 'views/js/string_routines.js',
        [],
        TPAY_PLUGIN_VERSION,
        true
    );
    wp_enqueue_script('tpay_gateway_js', plugin_dir_url(__FILE__) . 'views/js/main.min.js', [], time(), true);
    wp_enqueue_style('tpay_gateway_css', plugin_dir_url(__FILE__) . 'views/css/main.css', [], time());
}

function add_tpay_gateways($gateways)
{
    return array_merge($gateways, array_values(TPAY_CLASSMAP));
}

function generate_random_string($length = 10): string
{
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
}

function wp_config_put($slash = '')
{
    $hash = generate_random_string(64);
    $config = file_get_contents(ABSPATH . "wp-config.php");

    if (strpos($config, 'WP_TPAY_HASH') === false) {
        $config .= PHP_EOL . "define('WP_TPAY_HASH', '" . $hash . "');";
        file_put_contents(ABSPATH . $slash . "wp-config.php", $config);
    }

    $hash = generate_random_string();

    if (strpos($config, 'WP_TPAY_BLIK_PREFIX') === false) {
        $config .= PHP_EOL . "define('WP_TPAY_BLIK_PREFIX', '" . $hash . "');";
        file_put_contents(ABSPATH . $slash . "wp-config.php", $config);
    }
}

function tpay_on_activate()
{
    global $wpdb;
    $sql = 'CREATE TABLE if not exists `' . $wpdb->prefix . 'tpay_cards` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `vendor` varchar(20) DEFAULT NULL,
  `hash` text DEFAULT NULL,
  `short_code` int(11) DEFAULT NULL,
  `crc` varchar(64) DEFAULT NULL,
  `token` varchar(64) DEFAULT NULL,
  `source_order` int(11) DEFAULT NULL,
  `valid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;';
    $wpdb->get_results($sql);

    if (file_exists(ABSPATH . "wp-config.php") && is_writable(ABSPATH . "wp-config.php")) {
        wp_config_put();
    } else {
        if (file_exists(dirname(ABSPATH) . "/wp-config.php") && is_writable(dirname(ABSPATH) . "/wp-config.php")) {
            wp_config_put('/');
        } else {
            wc_add_notice(
                __(
                    'Cannot modify wp-config.php, you have to do it manually and add WP_TPAY_HASH and WP_TPAY_BLIK_PREFIX',
                    'tpay'
                ),
                'error'
            );
        }
    }
}

function tpay_refresh_checkout_on_payment_methods_change()
{
    wc_enqueue_js(
        "
       $( 'form.checkout' ).on( 'change', 'input[name^=\'payment_method\']', function() {
           $('body').trigger('update_checkout');
        });
   "
    );
}

function get_package_version(): string
{
    $installed = __DIR__ . '/vendor/composer/installed.php';

    if (false === file_exists($installed)) {
        throw new Exception('Composer installed file not found');
    }

    $dir = require $installed;

    if (isset($dir['versions']['tpay/woocommerce'])) {
        return $dir['versions']['tpay/woocommerce']['pretty_version'];
    }

    return 'n/a';
}

function tpay_blik0_transaction_status()
{
    check_ajax_referer('tpay-thank-you', 'nonce');

    $result = (new TpayBlik())->checkTransactionStatus(htmlspecialchars($_POST['transactionId']));

    wp_send_json($result);
}
