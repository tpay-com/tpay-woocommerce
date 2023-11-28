<?php

namespace Tpay\Ipn;

class UpdateBlikAlias implements IpnInterface
{
    public function parseNotification($response)
    {
        $uid = str_replace(WP_TPAY_BLIK_PREFIX.'_', '', $response['msg_value']['value']);
        switch ($response['event']) {
            case 'ALIAS_REGISTER':
                $this->saveBlikAlias($response['msg_value']['value'], $uid);
                break;
            case 'ALIAS_UNREGISTER':
                $this->removeBlikAlias($uid);
                break;
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
