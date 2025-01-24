<?php
/**
 * Plugin Name: Tpay Payment Gateway
 * Plugin URI: https://tpay.com
 * Description: Tpay payment gateway for WooCommerce
 * Version: 1.7.15
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
use Tpay\Api\Client;
use Tpay\Api\Transactions;
use Tpay\Blocks\PekaoInstallmentsBlock;
use Tpay\Blocks\TpayBlikBlock;
use Tpay\Blocks\TpayBlock;
use Tpay\Blocks\TpayCCBlock;
use Tpay\Blocks\TpayGenericBlock;
use Tpay\Blocks\TpaySFBlock;
use Tpay\Helpers\Cache;
use Tpay\OpenApi\Utilities\Logger;
use Tpay\PekaoInstallments;
use Tpay\Tpay;
use Tpay\TpayBlik;
use Tpay\TpayCC;
use Tpay\TpayLogger;
use Tpay\TpaySettings;
use Tpay\TpaySF;

require_once 'tpay-functions.php';

define('TPAY_PLUGIN_VERSION', '1.7.15');
define('TPAY_PLUGIN_DIR', dirname(plugin_basename(__FILE__)));
add_action('plugins_loaded', 'init_gateway_tpay');
register_activation_hook(__FILE__, 'tpay_on_activate');

const TPAYPBL = null;
const TPAYPBL_ID = 'tpaypbl';
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
    TPAYPEKAOINSTALLMENTS_ID => PekaoInstallments::class,
];

if ('disabled' != tpayOption('global_enable_fee')) {
    add_action('woocommerce_cart_calculate_fees', 'tpay_add_checkout_fee_for_gateway');
    add_action('woocommerce_after_checkout_form', 'tpay_refresh_checkout_on_payment_methods_change');
}

add_action('before_woocommerce_init', function () {
    if (class_exists(FeaturesUtil::class)) {
        FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__);
        FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__);
    }

    Logger::setLogger(new TpayLogger());
});

add_action('woocommerce_blocks_loaded', function () {
    if (!class_exists(AbstractPaymentMethodType::class)) {
        return;
    }

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (PaymentMethodRegistry $paymentMethodRegistry) {
            $paymentMethodRegistry->register(new TpayBlock());
            $paymentMethodRegistry->register(new TpayBlikBlock());
            $paymentMethodRegistry->register(new TpaySFBlock());
            $paymentMethodRegistry->register(new TpayCCBlock());
            $paymentMethodRegistry->register(new PekaoInstallmentsBlock());

            $generics = tpayOption('global_generic_payments');

            if (!empty($generics)) {
                $paymentMethodRegistry->register(new TpayGenericBlock());
            }
        }
    );
});

function init_gateway_tpay()
{
    tpay_config_init();

    if (!class_exists('WC_Payment_Gateway')) {
        childPluginHasParentPlugin();

        return;
    }

    load_plugin_textdomain('tpay', false, dirname(plugin_basename(__FILE__)).'/lang/');
    require_once realpath( __DIR__ . '/vendor/autoload.php' );
    Logger::setLogger(new TpayLogger());

    add_filter('woocommerce_payment_gateways', 'add_tpay_gateways');
    $genericsSelected = tpayOption('global_generic_payments') ?? [];

    if (is_admin()) {
        new TpaySettings();
    }

    $generics = array_map(static function (int $id) {
        return new class ($id) extends \Tpay\TpayGeneric {
            public function __construct($id = null)
            {
                parent::__construct("tpaygeneric-{$id}", $id);

                $channels = $this->channels();

                foreach ($channels as $channel) {
                    if ($channel->id === $id) {
                        $this->set_icon($channel->image->url);
                    }
                }
            }
        };
    }, $genericsSelected);

    add_filter('woocommerce_payment_gateways', function ($gateways) use ($generics) {
        return array_merge($gateways, $generics);
    });
}

if (is_admin()) {
    add_action('admin_enqueue_scripts', 'enqueue_tpay_admin_assets');
} else {
    add_action('wp_enqueue_scripts', 'enqueue_tpay_gateway_assets');
}

add_action('woocommerce_thankyou', function ($orderId) {
    $order = wc_get_order($orderId);

    if ('' === $order->get_meta('blik0')) {
        return;
    }

    wp_register_script(
        'tpay-thank-you',
        plugin_dir_url(__FILE__).'views/assets/thank-you.min.js',
        ['jquery'],
        false,
        true
    );
    wp_localize_script(
        'tpay-thank-you',
        'tpayThankYou',
        [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tpay-thank-you'),
            'transactionId' => $order->get_transaction_id(),
        ]
    );
    wp_enqueue_script('tpay-thank-you');
    wp_enqueue_style('tpay-thank-you', plugin_dir_url(__FILE__).'views/assets/thank-you.css', [], time());

    require 'views/html/thank-you-blik0.php';
}, 10, 2);

add_action('wp_ajax_tpay_blik0_transaction_status', 'tpay_blik0_transaction_status');
add_action('wp_ajax_nopriv_tpay_blik0_transaction_status', 'tpay_blik0_transaction_status');

add_filter('tpay_generic_gateway_list', function ($gateways) {
    $transactions = new Transactions(new Client(), new Cache());
    $channels = $transactions->channels();
    $genericGateways = [];

    foreach ($channels as $channel) {
        $genericGateways["tpaygeneric-{$channel->id}"] = [
            'name' => $channel->name,
            'front_name' => $channel->fullName,
            'default_description' => '',
            'api' => 'tpaygeneric-' . $channel->id,
            'group_id' => null,
        ];
    }

    return array_merge($gateways, $genericGateways);
});

add_action('woocommerce_after_add_to_cart_button', function () {
    if (tpayOption('tpay_settings_installments_product', 'woocommerce_pekaoinstallments_settings') !== 'yes') {
        return;
    }

    $merchantId = tpayOption('tpay_settings_installments_merchant_id', 'woocommerce_pekaoinstallments_settings');
    $asset = require plugin_dir_path(__FILE__) . 'views/assets/product.min.asset.php';

    wp_register_script(
        'tpay-product',
        plugin_dir_url(__FILE__) . 'views/assets/product.min.js',
        $asset['dependencies'],
        $asset['version'],
        true
    );
    wp_localize_script(
        'tpay-product',
        'tpayProduct',
        [
            'merchantId' => $merchantId,
            'translations' => [
                'button' => __('Calculate the installment!', 'tpay'),
            ],
        ]
    );
    wp_enqueue_script('tpay-product');
});

add_action('woocommerce_proceed_to_checkout', function () {
    if (tpayOption('tpay_settings_installments_cart', 'woocommerce_pekaoinstallments_settings') !== 'yes') {
        return;
    }

    $merchantId = tpayOption('tpay_settings_installments_merchant_id', 'woocommerce_pekaoinstallments_settings');
    $asset = require plugin_dir_path(__FILE__) . 'views/assets/cart.min.asset.php';

    wp_register_script(
        'tpay-cart',
        plugin_dir_url(__FILE__) . 'views/assets/cart.min.js',
        $asset['dependencies'],
        $asset['version'],
        true
    );
    wp_localize_script(
        'tpay-cart',
        'tpayCart',
        [
            'merchantId' => $merchantId,
            'translations' => [
                'button' => __('Calculate the installment!', 'tpay'),
            ],
        ]
    );
    wp_enqueue_script('tpay-cart');
});

add_action('woocommerce_review_order_before_payment', function () {
    if (tpayOption('tpay_settings_installments_checkout', 'woocommerce_pekaoinstallments_settings') !== 'yes') {
        return;
    }

    $merchantId = tpayOption('tpay_settings_installments_merchant_id', 'woocommerce_pekaoinstallments_settings');
    $amount = WC()->cart->get_cart_contents_total();

    $asset = require plugin_dir_path(__FILE__) . 'views/assets/checkout.min.asset.php';

    wp_register_script(
        'tpay-checkout',
        plugin_dir_url(__FILE__) . 'views/assets/checkout.min.js',
        $asset['dependencies'],
        $asset['version'],
        true
    );
    wp_localize_script(
        'tpay-checkout',
        'tpayCheckout',
        [
            'merchantId' => $merchantId,
            'amount' => $amount,
            'translations' => [
                'button' => __('Calculate the installment!', 'tpay'),
            ],
        ]
    );
    wp_enqueue_script('tpay-checkout');
});
