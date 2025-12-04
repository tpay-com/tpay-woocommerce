<?php

namespace Tpay\Ipn;

use Tpay\OpenApi\Utilities\TpayException;

class UpdateBlikAlias implements IpnInterface
{
    public function parseNotification($response)
    {
        [, $uid] = explode('_', $response['msg_value']['value'], 2);
        switch ($response['event']) {
            case 'ALIAS_REGISTER':
                $this->saveBlikAlias($response['msg_value']['value'], $uid);
                break;
            case 'ALIAS_UNREGISTER':
                $this->removeBlikAlias($uid);
                break;
            default:
                throw new TpayException('Unknown event: '.$response['event']);
        }
    }

    public function removeBlikAlias($uid)
    {
        delete_user_meta($uid, 'tpay_alias_blik');
        header('HTTP/1.1 200 OK');
        echo 'TRUE';
    }

    public function saveBlikAlias($alias, $uid)
    {
        update_user_meta($uid, 'tpay_alias_blik', $alias);
        header('HTTP/1.1 200 OK');
        echo 'TRUE';
    }
}
