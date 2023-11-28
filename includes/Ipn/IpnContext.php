<?php

namespace Tpay\Ipn;

class IpnContext
{
    public static function chooseStrategy($response)
    {
        if ($response['tr_status']) {
            $strategy = new UpdateOrderStatus();
        } elseif ($response['event']) {
            $strategy = new UpdateBlikAlias();
        }

        return new ReceiveNotification($response, $strategy);
    }
}
