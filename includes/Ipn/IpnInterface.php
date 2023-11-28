<?php

namespace Tpay\Ipn;

interface IpnInterface
{
    public function parseNotification($response);
}
