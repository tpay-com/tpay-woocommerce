<?php

namespace Tpay\Helpers;

use Tpay\TpayGateways;

class ConfigProvider
{
    public function get_config(TpayGateways $gateway)
    {
        $result = ['security_code' => null, 'api_key' => null, 'api_key_password' => null];
        if ('yes' === $gateway->get_option('use_global') || !$gateway->get_option('use_global')) {
            $result['security_code'] = wp_specialchars_decode($gateway->tpay_get_option(['tpay_settings_option_name', 'global_security_code']));
            $result['api_key'] = $gateway->tpay_get_option(['tpay_settings_option_name', 'global_api_key']);
            $result['api_key_password'] = $gateway->tpay_get_option(['tpay_settings_option_name', 'global_api_key_password']);
        } else {
            if (TPAYSF_ID != $gateway->id) {
                $result['security_code'] = wp_specialchars_decode($gateway->get_option('security_code'));
                $result['api_key'] = $gateway->get_option('api_key');
                $result['api_key_password'] = $gateway->get_option('api_key_password');
            } else {
                $sf_settings = get_option('woocommerce_tpaysf_settings');
                for ($i = 1; $i <= 10; $i++) {
                    if (rtrim($sf_settings['middomain'.$i], '/') == $gateway->site_domain) {
                        $gateway->valid_mid = $i;
                        break;
                    }
                }
                if ($gateway->valid_mid) {
                    $result['security_code'] = wp_specialchars_decode($gateway->get_option('security_code'.$gateway->valid_mid));
                    $result['api_key'] = $gateway->get_option('api_key'.$gateway->valid_mid);
                    $result['api_key_password'] = $gateway->get_option('api_key_password'.$gateway->valid_mid);
                }
            }
        }

        return $result;
    }
}
