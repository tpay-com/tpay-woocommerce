<?php

namespace Tpay\Ipn;

use Tpay\OpenApi\Model\Objects\NotificationBody\BlikAliasRegister;
use Tpay\OpenApi\Model\Objects\NotificationBody\BlikAliasUnregister;
use Tpay\OpenApi\Utilities\TpayException;

class UpdateBlikAlias implements IpnInterface
{
    public function handle($notification): void
    {
        $alias = $notification->value->getValue();
        [, $uid] = explode('_', $alias, 2);

        if ($notification instanceof BlikAliasRegister) {
            update_user_meta($uid, 'tpay_alias_blik', $alias);

            return;
        }
        if ($notification instanceof BlikAliasUnregister) {
            delete_user_meta($uid, 'tpay_alias_blik');

            return;
        }

        throw new TpayException('Invalid notification object for BLIK strategy');
    }
}
