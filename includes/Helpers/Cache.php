<?php

namespace Tpay\Helpers;

class Cache
{
    public function get($key)
    {
        $key .= ':';
        $key .= @get_option('tpay_settings_option_name')['global_tpay_environment'];
        $file = $this->getCacheDir().md5($key).'.php';
        if (file_exists($file)) {
            $data = require $file;
            if ($data['ttl'] > time()) {
                return unserialize(base64_decode($data['data']));
            }
            unlink($file);
        }
    }

    public function set($key, $value, $ttl = 3600)
    {
        $key .= ':';
        $key .= @get_option('tpay_settings_option_name')['global_tpay_environment'];
        $file = $this->getCacheDir().md5($key).'.php';
        $ttl += time();
        $data = base64_encode(serialize($value));
        $fileContent = "return ['ttl' => {$ttl}, 'data' => '{$data}'];";

        file_put_contents($file, "<?php {$fileContent}");
    }

    public function erase()
    {
        foreach (glob($this->getCacheDir().'*') as $file) {
            if ($file === $this->getCacheDir()) {
                continue;
            }
            unlink($file);
        }
    }

    private function getCacheDir()
    {
        return __DIR__.'/../../cache/';
    }
}
