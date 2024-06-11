<?php

namespace Tpay\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Tpay\TpayTwisto;

final class TpayTwistoBlock extends AbstractPaymentMethodType
{
    protected $name = 'tpaytwisto';
    protected $settings;

    /** @var TpayTwisto */
    private $gateway;

    public function initialize()
    {
        $this->settings = get_option('woocommerce_tpaytwisto_settings', []);
        $this->gateway = new TpayTwisto();
    }

    public function is_active(): bool
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles(): array
    {
        wp_register_script('tpaytwisto', plugin_dir_url(__DIR__).'../views/js/checkout.min.js', [
            'wc-blocks-registry',
            'wc-settings',
            'wp-element',
            'wp-html-entities',
            'wp-i18n',
            'react',
        ], null, true);

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('tpay-blocks-integration');
        }

        return ['tpaytwisto'];
    }

    public function get_payment_method_data(): array
    {
        ob_start();
        $this->gateway->payment_fields();
        $fields = ob_get_clean();

        return [
            'title' => $this->gateway->get_title(),
            'description' => $this->gateway->get_description(),
            'icon' => $this->gateway->icon,
            'cartTotal' => WC()->cart ? WC()->cart->get_cart_contents_total() : null,
            'fields' => $fields,
            'supports' => array_filter($this->gateway->supports, [$this->gateway, 'supports']),
        ];
    }
}
