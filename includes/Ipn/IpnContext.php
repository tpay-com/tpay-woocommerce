<?php

namespace Tpay\Ipn;

use Tpay\Helpers\Cache;
use Tpay\Helpers\GatewayHelper;
use Tpay\OpenApi\Model\Objects\NotificationBody\BasicPayment;
use Tpay\OpenApi\Model\Objects\NotificationBody\BlikAliasRegister;
use Tpay\OpenApi\Model\Objects\NotificationBody\BlikAliasUnregister;
use Tpay\OpenApi\Utilities\CacheCertificateProvider;
use Tpay\OpenApi\Utilities\TpayException;
use Tpay\OpenApi\Webhook\JWSVerifiedPaymentNotification;

class IpnContext
{
    public function handle($config): void
    {
        $gatewayHelper = new GatewayHelper();
        $certificateProvider = new CacheCertificateProvider(new Cache());

        $webhook = new JWSVerifiedPaymentNotification(
            $certificateProvider,
            $config['security_code'],
            $gatewayHelper->is_prod()
        );

        $notification = $webhook->getNotification();

        $strategy = $this->resolveStrategy($notification);

        $strategy->handle($notification);
    }

    public static function resolveStrategy($notification): IpnInterface
    {
        if ($notification instanceof BasicPayment) {
            return new UpdateOrderStatus();
        }

        if (
            $notification instanceof BlikAliasRegister
            || $notification instanceof BlikAliasUnregister
        ) {
            return new UpdateBlikAlias();
        }

        throw new TpayException('Unsupported notification type: '.get_class($notification));
    }
}
