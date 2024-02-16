<?php

namespace Tpay\Helpers;

class Cache
{
    public function get($key)
    {
        $key .= ':';
        $key .= @get_option('tpay_settings_option_name')['global_tpay_environment'];
        $file = $this->getCacheDir().md5($key);

        if (file_exists($file)) {
            $data = unserialize(base64_decode(file_get_contents($file)));

            if ($data['ttl'] > time()) {
                return $data['data'];
            }

            unlink($file);
        }
    }

    public function set($key, $value, $ttl = 3600)
    {
        $key .= ':';
        $key .= @get_option('tpay_settings_option_name')['global_tpay_environment'];
        $file = $this->getCacheDir().md5($key);
        $ttl += time();
        $data = base64_encode(serialize(['ttl' => $ttl, 'data' => $value]));

        file_put_contents($file, $data);
    }

    public function erase(): void
    {
        foreach (glob($this->getCacheDir().'*') as $file) {
            if ($file === $this->getCacheDir()) {
                continue;
            }
            unlink($file);
        }
    }

    private function getCacheDir(): string
    {
        return __DIR__.'/../../cache/';
    }
}
