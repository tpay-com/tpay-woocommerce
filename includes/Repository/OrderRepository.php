<?php

declare(strict_types=1);

namespace Tpay\Repository;

use Tpay\Helpers\GatewayHelper;
use WC_Order;

class OrderRepository
{
    private $gatewayHelper;

    public function __construct()
    {
        $this->gatewayHelper = new GatewayHelper();
    }

    public function orderByCrc(string $crc): ?WC_Order
    {
        $order = wc_get_orders(['meta_key' => 'crc', 'meta_value' => $crc]);

        if (null === $order) {
            $order = wc_get_orders(['meta_key' => '_crc', 'meta_value' => $crc]);
        }

        if (count($order) > 1) {
            $this->gatewayHelper->tpay_logger('Pobrano zbyt wiele zamówień. Liczba zamówień: '.count($order));
        }

        if (isset($order[0])) {
            return $order[0];
        }

        global $wpdb;

        $sql = $wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %s AND meta_key IN ('_crc', 'crc')", $crc);
        $orderId = $wpdb->get_var($sql);

        if ($orderId) {
            return wc_get_order($orderId);
        }

        $sql = $wpdb->prepare("SELECT order_id FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key IN ('_crc', 'crc') AND meta_value = %s", $crc);
        $orderId = $wpdb->get_var($sql);

        if ($orderId) {
            return wc_get_order($orderId);
        }

        return null;
    }
}
