<?php

namespace Tpay\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Tpay\TpayGeneric;

final class TpayGenericBlock extends AbstractPaymentMethodType
{
    protected $name = 'tpaygeneric';
    protected $settings;
    private $gateway;

    public function initialize()
    {
        $this->settings = get_option('woocommerce_tpaygeneric_settings', []);
        $this->gateway = new class () extends TpayGeneric {
            public function __construct($id = null)
            {
                parent::__construct('tpaygeneric-1');

                $channels = $this->channels();

                foreach ($channels as $channel) {
                    if ($channel->id === $id) {
                        $this->set_icon($channel->image->url);
                    }
                }
            }
        };
    }

    public function is_active(): bool
    {
        return true;
    }

    public function get_payment_method_script_handles(): array
    {
        wp_register_script('tpaygeneric', plugin_dir_url(__DIR__).'../views/js/checkout-generic.min.js', [
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

        return ['tpaygeneric'];
    }

    public function get_payment_method_data(): array
    {
        ob_start();
        $this->gateway->payment_fields();
        $fields = ob_get_clean();
        $config = [];

        $channels = $this->gateway->channels();
        $generics = tpayOption('global_generic_payments');
        $availablePayments = WC()->payment_gateways()->get_available_payment_gateways();

        foreach ($channels as $channel) {
            if (in_array($channel->id, $generics) && in_array("tpaygeneric-{$channel->id}", array_keys($availablePayments))) {
                $config[$channel->id] = [
                    'id' => $channel->id,
                    'title' => $channel->fullName,
                    'icon' => $channel->image->url,
                    'fields' => $fields,
                ];
            }
        }

        return $config;
    }
}
