<?php

namespace Tpay\Ipn;

use Automattic\WooCommerce\Enums\OrderInternalStatus;
use RuntimeException;
use Tpay\Helpers;
use Tpay\OpenApi\Model\Objects\NotificationBody\BasicPayment;
use Tpay\OpenApi\Utilities\TpayException;
use Tpay\Repository\OrderRepository;
use WC_Order;

class UpdateOrderStatus implements IpnInterface
{
    private $gateway_helper;
    private $card_helper;
    private $orderRepository;

    public function __construct()
    {
        $this->gateway_helper = new Helpers\GatewayHelper();
        $this->card_helper = new Helpers\CardHelper();
        $this->orderRepository = new OrderRepository();
    }

    public function handle($notification): void
    {
        if (!$notification instanceof BasicPayment) {
            throw new TpayException('Invalid notification object for payment strategy');
        }

        if ($notification->isTestNotification()) {
            $this->gateway_helper->tpay_logger(
                'Odebrano testowe powiadomienie: '.print_r($notification->getNotificationAssociative(), 1)
            );

            return;
        }

        $crc = $notification->tr_crc->getValue();
        $status = $notification->tr_status->getValue();

        $order = $this->orderRepository->orderByCrc($crc);

        if (!$order) {
            throw new TpayException('Order not found');
        }

        if (!$this->validateAmount($order, $notification)) {
            $this->gateway_helper->tpay_logger(
                sprintf(
                    'Niezgodna kwota zamówienia: order=%s, notification=%s',
                    $order->get_total(),
                    $notification->tr_amount->getValue()
                )
            );

            throw new RuntimeException('Niezgodna kwota zamówienia');
        }

        switch ($status) {
            case 'TRUE':
            case 'PAID':
                $this->completeOrder($order, $notification);
                break;
            case 'CHARGEBACK':
                $order->update_status('refunded');
                break;
            case 'FALSE':
                $this->gateway_helper->tpay_logger(
                    'Przyjęto zgłoszenie z bramki Tpay, że płatność za zamówienie nie powiodło się. Zrzut: '.print_r($notification->getNotificationAssociative(), 1)
                );
                break;
            default:
                throw new TpayException('Unknown notification status: '.$status);
        }
    }

    public function completeOrder(WC_Order $order, BasicPayment $notification): void
    {
        $order->payment_complete($notification->tr_id->getValue());

        $status = $this->getOrderStatus($order);
        $crc = $notification->tr_crc->getValue();
        $order->update_status(
            $status,
            sprintf(
                '%s : %s. ',
                __('CRC number in Tpay', 'tpay'),
                $crc
            )
        );

        $this->gateway_helper->tpay_logger(
            'Odebrano powiadomienie dla zamówienia: '.$order->get_id().', transakcja: '.$order->get_transaction_id()
        );

        $cardToken = $notification->card_token->getValue();
        if (null !== $cardToken) {
            $this->gateway_helper->tpay_logger(
                'Powiadomienie do: '.$order->get_transaction_id().' zawiera stokenizowaną kartę.'
            );
            $this->saveUserCard($crc, $cardToken);
        }
    }

    public function saveUserCard(string $crc, string $token)
    {
        if ($order = $this->orderRepository->orderByCrc($crc)) {
            $this->gateway_helper->tpay_logger('Zapisanie tokenu karty');
            $this->card_helper->update_card_token(
                $order->get_customer_id(),
                $crc,
                $token,
                $order->get_id()
            );
        }
    }

    private function getOrderStatus(WC_Order $order): string
    {
        $checkedStatus = 'global_default_virtual_product_on_hold_status';

        if ($order->needs_processing()) {
            $checkedStatus = 'global_default_on_hold_status';
        }

        return tpayOption($checkedStatus) ?? OrderInternalStatus::PROCESSING;
    }

    private function validateAmount(WC_Order $order, BasicPayment $notification): bool
    {
        return number_format((float) $order->get_total(), 2, '.', '') ===
            number_format((float) $notification->tr_amount->getValue(), 2, '.', '');
    }
}
