<?php
/**
 * Plugin Name: Tpay Payment Gateway
 * Plugin URI: https://tpay.com
 * Description: Tpay payment gateway for WooCommerce
 * Version: 1.5.0
 * Author: Krajowy Integrator Płatności S.A.
 * Author URI: http://www.tpay.com
 * License: LGPL 3.0
 * Text Domain: tpay
 * Domain Path: /lang
 * WC requires at least: 5.5
 * WC tested up to: 5.5
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Tpay\PekaoInstallments;
use Tpay\Tpay;
use Tpay\TpayBlik;
use Tpay\TpayCC;
use Tpay\TpayGPay;
use Tpay\TpayInstallments;
use Tpay\TpaySettings;
use Tpay\TpaySF;
use Tpay\TpayTwisto;

require_once 'tpay-functions.php';

define('TPAY_PLUGIN_VERSION', '1.4.5');
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


if (tpayOption('global_enable_fee') != 'disabled') {
    add_action('woocommerce_cart_calculate_fees', 'tpay_add_checkout_fee_for_gateway');
    add_action('woocommerce_after_checkout_form', 'tpay_refresh_checkout_on_payment_methods_change');
}

add_action('before_woocommerce_init', function () {
    if (class_exists(FeaturesUtil::class)) {
        FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__);
        FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__);
    }
});

add_action('woocommerce_blocks_loaded', function () {
    if (!class_exists(AbstractPaymentMethodType::class)) {
        return;
    }

    add_action('woocommerce_blocks_payment_method_type_registration', function (PaymentMethodRegistry $paymentMethodRegistry) {
        $paymentMethodRegistry->register(new \Tpay\Blocks\TpayBlock());
        $paymentMethodRegistry->register(new \Tpay\Blocks\TpayBlikBlock());
        $paymentMethodRegistry->register(new \Tpay\Blocks\TpaySFBlock());
        $paymentMethodRegistry->register(new \Tpay\Blocks\TpayCCBlock());
        $paymentMethodRegistry->register(new \Tpay\Blocks\TpayGPayBlock());
        $paymentMethodRegistry->register(new \Tpay\Blocks\TpayInstallmentsBlock());
        $paymentMethodRegistry->register(new \Tpay\Blocks\TpayTwistoBlock());
        $paymentMethodRegistry->register(new \Tpay\Blocks\PekaoInstallmentsBlock());
    });
});

add_filter('woocommerce_order_data_store_cpt_get_orders_query', 'handle_custom_query_var', 10, 2);

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

if (is_admin()) {
    add_action('admin_enqueue_scripts', 'enqueue_tpay_admin_assets');
} else {
    add_action('wp_enqueue_scripts', 'enqueue_tpay_gateway_assets');
}
