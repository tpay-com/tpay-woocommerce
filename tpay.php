<?php
/**
 * Plugin Name: Tpay Payment Gateway
 * Plugin URI: https://tpay.com
 * Description: Tpay payment gateway for WooCommerce
 * Version: 1.4.4
 * Author: Krajowy Integrator Płatności S.A.
 * Author URI: http://www.tpay.com
 * License: LGPL 3.0
 * Text Domain: tpay
 * Domain Path: /lang
 * WC requires at least: 5.5
 * WC tested up to: 5.5
 */

/*
 * Add new gateway
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Tpay\PekaoInstallments;
use Tpay\Tpay;
use Tpay\TpayBlik;
use Tpay\TpayCC;
use Tpay\TpayGateways;
use Tpay\TpayGPay;
use Tpay\TpayInstallments;
use Tpay\TpaySettings;
use Tpay\TpaySF;
use Tpay\TpayTwisto;

define('TPAY_PLUGIN_VERSION', '1.4.4');
define('TPAY_PLUGIN_DIR', dirname(plugin_basename(__FILE__)));
add_action('plugins_loaded', 'init_gateway_tpay');
register_activation_hook(__FILE__, 'tpay_on_activate');


const TPAYPBL = null;
const TPAYPBL_ID = 'tpaypbl';
const TPAYGPAY = 166;
const TPAYGPAY_ID = 'tpaygpay';
const TPAYTWISTO = 167;
const TPAYTWISTO_ID = 'tpaytwisto';
const TPAYINSTALLMENTS = 109;
const TPAYINSTALLMENTS_ID = 'tpayinstallments';
const TPAYCC = 103;
const TPAYCC_ID = 'tpaycc';
const TPAYSF = 103;
const TPAYSF_ID = 'tpaysf';
const TPAYBLIK = 150;
const TPAYBLIK_ID = 'tpayblik';
const TPAYPEKAOINSTALLMENTS = 169;
const TPAYPEKAOINSTALLMENTS_ID = 'pekaoinstallments';

const TPAY_CLASSMAP = [
    TPAYPBL_ID => Tpay::class,
    TPAYBLIK_ID => TpayBlik::class,
    TPAYCC_ID => TpayCC::class,
    TPAYSF_ID => TpaySF::class,
    TPAYGPAY_ID => TpayGPay::class,
    TPAYTWISTO_ID => TpayTwisto::class,
    TPAYINSTALLMENTS_ID => TpayInstallments::class,
    TPAYPEKAOINSTALLMENTS_ID => PekaoInstallments::class
];


if (get_option('tpay_settings_option_name')['global_enable_fee'] != 'disabled') {
    add_action('woocommerce_cart_calculate_fees', 'tpay_add_checkout_fee_for_gateway');
    add_action('woocommerce_after_checkout_form', 'tpay_refresh_checkout_on_payment_methods_change');
}

add_action('before_woocommerce_init', function () {
    if (class_exists(FeaturesUtil::class)) {
        FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__);
    }
});

function handle_custom_query_var(array $query, array $query_vars): array
{
    if (!empty($query_vars['crc'])) {
        $query['meta_query'][] = array(
            'key' => '_crc',
            'value' => esc_attr($query_vars['crc']),
        );
    }

    return $query;
}

add_filter('woocommerce_order_data_store_cpt_get_orders_query', 'handle_custom_query_var', 10, 2);


function tpay_add_checkout_fee_for_gateway()
{
    $chosen_gateway = WC()->session->get('chosen_payment_method');
    $type = get_option('tpay_settings_option_name')['global_enable_fee'];
    if ($type == 'amount') {
        $fee = get_option('tpay_settings_option_name')['global_amount_fee'];
    } else {
        $percentage = get_option('tpay_settings_option_name')['global_percentage_fee'];
        $amonut = WC()->cart->cart_contents_total + WC()->cart->shipping_total;
        $fee = $percentage / 100 * $amonut;
    }
    $fee = round($fee, 2);
    if (array_key_exists($chosen_gateway, TpayGateways::gateways_list())) {
        WC()->cart->add_fee(__('Transaction fee', 'tpay'), $fee);
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

function generate_random_string($length = 10)
{
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function init_gateway_tpay()
{
    if (!class_exists('WC_Payment_Gateway')) {
        childPluginHasParentPlugin();

        return;
    }
    load_plugin_textdomain('tpay', false, dirname(plugin_basename(__FILE__)) . '/lang/');
    require_once('vendor/autoload.php');
    new TpaySettings();
    add_filter('woocommerce_payment_gateways', 'add_tpay_gateways');
}

function add_tpay_gateways($gateways)
{
    return array_merge($gateways, array_values(TPAY_CLASSMAP));
}

if (is_admin()) {
    add_action('admin_enqueue_scripts', 'enqueue_tpay_admin_assets');
} else {
    add_action('wp_enqueue_scripts', 'enqueue_tpay_gateway_assets');
}


//enqueue assets
function enqueue_tpay_admin_assets()
{
//    wp_enqueue_script('tpay_admin_js', plugin_dir_url(__FILE__) . 'views/js/admin.min.js', [], TPAY_PLUGIN_VERSION);
    wp_enqueue_script('tpay_admin_js', plugin_dir_url(__FILE__) . 'views/js/admin.min.js', [], time());
    wp_enqueue_style('tpay_admin_css', plugin_dir_url(__FILE__) . 'views/css/admin.css', [], TPAY_PLUGIN_VERSION);
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

function displayChildPluginNotice()
{
    echo sprintf(
        '<div class="error"><p>%s <a target="_blank" href="https://wordpress.org/plugins/woocommerce/">%s</a></p></div>',
        __('Tpay requires a WooCommerce plugin,', 'tpay'),
        __('download it', 'tpay')
    );
}

