<?php

namespace Tpay\Ipn;

use Tpay\Helpers\GatewayHelper;

class IpnContext
{
    public static function chooseStrategy($response)
    {
        if (isset($response['tr_status'])) {
            $strategy = new UpdateOrderStatus();
        } elseif (isset($response['event'])) {
            $strategy = new UpdateBlikAlias();
        } else {
            $text =  json_encode($response);
            (new GatewayHelper())->tpay_logger('Mismatched strategy. Response content:  '.$text);
        }

        return new ReceiveNotification($response, $strategy);
    }
}
