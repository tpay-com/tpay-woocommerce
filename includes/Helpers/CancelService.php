<?php

namespace Tpay\Helpers;

use Automattic\WooCommerce\Enums\OrderInternalStatus;
use DateInterval;
use DateTime;
use Throwable;
use Tpay\Api\Client;
use Tpay\TpayGateways;
use Tpay\TpaySettings;
use WC_Order;

class CancelService
{
    private Client $tpayClient;

    public function __construct()
    {
        $this->tpayClient = new Client();
    }

    public function process()
    {
        $orders = $this->getCancellableOrders();
        $logger = wc_get_logger();

        foreach ($orders as $order) {
            try {
                $this->cancelTransaction($order);
            } catch (Throwable $e) {
                $logger->warning('Failed to cancel order '.$order->get_id().': '.$e->getMessage(), ['exception' => $e, 'source' => 'tpay']);
            }
        }
    }

    private function getCancellableOrders(): array
    {
        $period = tpayOption('global_generic_auto_cancel_days');
        if (!$period) {
            $period = TpaySettings::CANCEL_DEFAULT_PERIOD;
        }

        $date = new DateTime();
        $date->sub(new DateInterval('P'.$period.'D'));
        $initialDate = $date->format('Y-m-d');

        $args = [
            'limit' => -1,
            'type' => 'shop_order',
            'status' => [OrderInternalStatus::PENDING],
            'date_created' => '<'.$initialDate,
        ];

        return wc_get_orders($args);
    }

    private function cancelTransaction(WC_Order $order): void
    {
        $tpayMethods = array_keys(TpayGateways::gateways_list());
        $isTpayOrder = in_array($order->get_payment_method(), $tpayMethods);
        if (!$isTpayOrder) {
            return;
        }
        $transactionId = $order->get_transaction_id();
        $api = $this->tpayClient->connect();
        $api->transactions()->cancelTransaction($transactionId);
        $order->update_status(OrderInternalStatus::CANCELLED, __('Cancelled automatically due to lack of payment', 'tpay'));
    }
}
