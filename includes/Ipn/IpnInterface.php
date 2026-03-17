<?php

namespace Tpay\Ipn;

interface IpnInterface
{
    public function handle($notification): void;
}
