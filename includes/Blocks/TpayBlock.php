<?php

namespace Tpay\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Tpay\Tpay;

final class TpayBlock extends AbstractPaymentMethodType
{
    protected $name = 'tpaypbl';
    protected $settings;

    /** @var Tpay */
    private $gateway;

    public function initialize()
    {
        $this->settings = get_option('woocommerce_tpaypbl_settings', []);
        $this->gateway = new Tpay();
    }

    public function is_active(): bool
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles(): array
    {
        $assetPath = plugin_dir_path(__FILE__).'views/assets/checkout.min.asset.php';
        $dependencies = [];
        $version = TPAY_PLUGIN_VERSION;

        if (file_exists($assetPath)) {
            $asset = require $assetPath;
            $version = $asset['version'] ?? TPAY_PLUGIN_VERSION;
            $dependencies = $asset['dependencies'] ?? [];
        }

        wp_register_script(
            'tpaypbl',
            plugin_dir_url(__DIR__).'../views/assets/checkout-blocks.min.js',
            $dependencies,
            $version,
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('tpay-blocks-integration');
        }

        return ['tpaypbl'];
    }

    public function get_payment_method_data(): array
    {
        ob_start();
        $this->gateway->payment_fields();
        $fields = ob_get_clean();

        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'channels' => $this->gateway->channels(),
            'icon' => $this->gateway->icon,
            'tpayDirect' => $this->gateway->isBankSelectionHidden(),
            'cartTotal' => WC()->cart ? WC()->cart->get_cart_contents_total() : null,
            'fields' => $fields,
            'supports' => array_filter($this->gateway->supports, [$this->gateway, 'supports']),
            'channelNotSelectedMessage' => __('Select a bank', 'tpay'),
        ];
    }
}
