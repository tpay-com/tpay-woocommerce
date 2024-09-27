<?php

namespace Tpay\Api;

use Exception;
use Tpay\Helpers\GatewayHelper;
use Tpay\OpenApi\Api\TpayApi;

class Client
{
    /** @var null|string */
    protected $apiKey;

    /** @var null|string */
    protected $apiKeyPassword;

    /** @var null|string */
    protected $securityCode;

    /** @var GatewayHelper */
    protected $gatewayHelper;

    /** @var null|false|TpayApi */
    protected static $api;

    public function __construct()
    {
        $this->gatewayHelper = new GatewayHelper();
    }

    public function connect()
    {
        if (null !== self::$api) {
            return self::$api;
        }

        $this->apiKey = tpayOption('global_api_key');
        $this->apiKeyPassword = tpayOption('global_api_key_password');
        $this->securityCode = tpayOption('global_security_code');

        try {
            $isProd = 'sandbox' != tpayOption('global_tpay_environment');
            self::$api = new TpayApi($this->apiKey, $this->apiKeyPassword, $isProd, 'read', null, buildInfo());
            self::$api->authorization();

            return self::$api;
        } catch (Exception $exception) {
            $this->gatewayHelper->tpay_logger('Bramka Tpay nie została uruchomiona - brak danych lub dane niepoprawne');
            self::$api = false; // microcache that tpay connection is unavailable
            if (is_admin() && strpos($exception->getMessage(), 'Authorization error')) {
                add_settings_error(
                    'general',
                    'settings_updated',
                    'Tpay: Authorization error, wrong credentials.'
                );
            }

            return false;
        }
    }
}
