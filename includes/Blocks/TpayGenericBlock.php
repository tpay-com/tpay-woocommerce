<?php

namespace Tpay\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Tpay\TpayGeneric;

final class TpayGenericBlock extends AbstractPaymentMethodType
{
    protected $name = 'tpaygeneric';
    protected $settings;

    /** @var TpayGeneric */
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
        $assetPath = plugin_dir_path(__FILE__).'views/assets/checkout.min.asset.php';
        $dependencies = [];
        $version = TPAY_PLUGIN_VERSION;

        if (file_exists($assetPath)) {
            $asset = require $assetPath;
            $version = $asset['version'] ?? TPAY_PLUGIN_VERSION;
            $dependencies = $asset['dependencies'] ?? [];
        }

        wp_register_script(
            'tpaygeneric',
            plugin_dir_url(__DIR__).'../views/assets/checkout-blocks.min.js',
            $dependencies,
            $version,
            true
        );

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
                    'constraints' => $channel->constraints,
                    'total' => $this->gateway->getCartTotal(),
                ];
            }
        }

        return $config;
    }
}
