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
            $text = implode("\n", array_map(fn($k, $v) => "$k:$v", array_keys($response), $response));
            (new GatewayHelper())->tpay_logger('Mismatched strategy. Response content:  ' . $text);
        }

        return new ReceiveNotification($response, $strategy);
    }
}
