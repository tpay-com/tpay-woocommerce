<?php

declare(strict_types=1);

namespace Tpay;

use Psr\Log\LoggerInterface;

class TpayLogger implements LoggerInterface
{
    public function emergency($message, array $context = [])
    {
        // TODO: Implement emergency() method.
    }

    public function alert($message, array $context = [])
    {
        // TODO: Implement alert() method.
    }

    public function critical($message, array $context = [])
    {
        // TODO: Implement critical() method.
    }

    public function error($message, array $context = [])
    {
        // TODO: Implement error() method.
    }

    public function warning($message, array $context = [])
    {
        // TODO: Implement warning() method.
    }

    public function notice($message, array $context = [])
    {
        // TODO: Implement notice() method.
    }

    public function info($message, array $context = [])
    {
        // TODO: Implement info() method.
    }

    public function debug($message, array $context = [])
    {
        // TODO: Implement debug() method.
    }

    public function log($level, $message, array $context = [])
    {
        wc_get_logger()->log($level, $message, $context);
    }
}
