<?php

namespace Tpay\Helpers;

class Cache
{
    public function get($key)
    {
        $key .= ':';
        $key .= tpayOption('global_tpay_environment');

        return get_transient($key);
    }

    public function set($key, $value, $ttl = 3600)
    {
        $key .= ':';
        $key .= tpayOption('global_tpay_environment');
        set_transient($key, $value, $ttl);
    }

    public function erase(): void
    {
        global $wpdb;
        $transients = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_%' AND option_name LIKE '%tpay%'");

        foreach ($transients as $transient) {
            $transient_key = str_replace(['_transient_', '_transient_timeout_'], '', $transient);
            delete_transient($transient_key);
        }
    }
}
