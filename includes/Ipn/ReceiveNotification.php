<?php

namespace Tpay\Ipn;

class ReceiveNotification
{
    public $response;
    private $strategy;

    public function __construct($response, IpnInterface $strategy)
    {
        $this->response = $response;
        $this->strategy = $strategy;
        $this->strategy->parseNotification($response);
    }
}
