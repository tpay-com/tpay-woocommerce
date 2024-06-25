<?php

namespace Tpay\Helpers;

use Tpay\TpayGateways;

class ConfigProvider
{
    public function get_config(TpayGateways $gateway): array
    {
        if ('yes' === $gateway->get_option('use_global') || !$gateway->get_option('use_global')) {
            $result['security_code'] = wp_specialchars_decode($gateway->tpay_get_option(['tpay_settings_option_name', 'global_security_code']));
            $result['api_key'] = $gateway->tpay_get_option(['tpay_settings_option_name', 'global_api_key']);
            $result['api_key_password'] = $gateway->tpay_get_option(['tpay_settings_option_name', 'global_api_key_password']);
        } else {
            $result['security_code'] = wp_specialchars_decode($gateway->get_option('security_code'));
            $result['api_key'] = $gateway->get_option('api_key');
            $result['api_key_password'] = $gateway->get_option('api_key_password');
        }

        return $result;
    }
}
