<?php

namespace Tpay\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Tpay\TpayBlik;

final class TpayBlikBlock extends AbstractPaymentMethodType
{
    protected $name = 'tpayblik';
    protected $settings;

    /** @var TpayBlik */
    private $gateway;

    public function initialize()
    {
        $this->settings = get_option('woocommerce_tpayblik_settings', []);
        $this->gateway = new TpayBlik();
    }

    public function is_active(): bool
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles(): array
    {
        wp_register_script('tpayblik', plugin_dir_url(__DIR__).'../views/js/checkout.min.js', [
            'wc-blocks-registry',
            'wc-settings',
            'wp-element',
            'wp-html-entities',
            'wp-i18n',
            'wp-hooks',
            'react',
        ], null, true);

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('tpay-blocks-integration');
        }

        return ['tpayblik'];
    }

    public function get_payment_method_data(): array
    {
        ob_start();
        $this->gateway->payment_fields();
        $fields = ob_get_clean();

        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => array_filter($this->gateway->supports, [$this->gateway, 'supports']),
            'fields' => $fields,
            'icon' => $this->gateway->icon,
            'blikZero' => $this->gateway->isBlikZeroEnabled(),
            'blikCodeNotTyped' => __('invalid BLIK six-digit code', 'tpay'),
        ];
    }
}
