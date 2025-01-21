<?php

namespace Tpay\Ipn;

use Exception;
use Tpay\Helpers;
use Tpay\OpenApi\Webhook\JWSVerifiedPaymentNotification;
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

    public function parseNotification($response)
    {
        $order = $this->orderRepository->orderByCrc($response['tr_crc']);

        if (!$order) {
            echo 'FALSE - no order found!';
            exit();
        }

        $order_method = $order->get_payment_method();
        $class = TPAY_CLASSMAP[$order_method] ?? null;

        if (!class_exists($class)) {
            $class = new class ($order_method) extends \Tpay\TpayGeneric {
                public function __construct($id = null)
                {
                    parent::__construct("tpaygeneric-{$id}", (int) $id);

                    $channels = $this->channels();

                    foreach ($channels as $channel) {
                        if ($channel->id === $id) {
                            $this->set_icon($channel->image->url);
                        }
                    }
                }
            };
        }

        $gateway = new $class();
        $config = (new Helpers\ConfigProvider())->get_config($gateway);

        $isProd = 'sandbox' != tpayOption('global_tpay_environment');
        try {
            $NotificationWebhook = new JWSVerifiedPaymentNotification(
                $config['security_code'],
                $isProd
            );
            $notification = $NotificationWebhook->getNotification();
            $notificationData = $notification->getNotificationAssociative();
        } catch (Exception $e) {
            $this->gateway_helper->tpay_logger($e->getMessage());
            echo 'FALSE';
            exit();
        }

        switch ($notificationData['tr_status']) {
            case 'TRUE':
            case 'PAID':
                $this->orderIsComplete($order, $notificationData);
                break;
            case 'CHARGEBACK':
                $order->update_status('refunded');
                $this->orderIsRefunded();
                break;
            case 'FALSE':
                $this->orderIsNotComplete($notificationData);
                break;
        }
    }

    public function orderIsRefunded()
    {
        header('HTTP/1.1 200 OK');
        echo 'TRUE';
        exit();
    }

    public function orderIsComplete(WC_Order $order, array $response): void
    {
        $status = $this->getOrderStatus($order);
        $order->update_status($status, sprintf('%s : %s. ', __('CRC number in Tpay', 'tpay'), $response['tr_crc']));
        $order->payment_complete($order->get_transaction_id());
        $this->gateway_helper->tpay_logger('Odebrano powiadomienie dla zamówienia: '.$order->get_id().', transakcja: '.$order->get_transaction_id());

        if (isset($response['card_token'])) {
            $this->gateway_helper->tpay_logger('Powiadomienie do: '.$order->get_transaction_id().' zawiera stokenizowaną kartę.');
            $this->saveUserCard($response);
        }

        header('HTTP/1.1 200 OK');
        echo 'TRUE';
        exit();
    }

    public function orderIsNotComplete($response)
    {
        $this->gateway_helper->tpay_logger(
            'Przyjęto zgłoszenie z bramki Tpay, że płatność za zamówienie nie powiodło się. Zrzut: '.print_r($response, 1)
        );
        header('HTTP/1.1 200 OK');
        echo 'FALSE';
        exit();
    }

    public function saveUserCard($response)
    {
        $crc = $response['tr_crc'];

        if ($order = $this->orderRepository->orderByCrc($response['tr_crc'])) {
            $this->gateway_helper->tpay_logger('Zapisanie tokenu karty');
            $this->card_helper->update_card_token(
                $order->get_customer_id(),
                $crc,
                $response['card_token'],
                $order->get_id()
            );
        }
    }

    private function getOrderStatus(WC_Order $order): string
    {
        $checkedStatus = 'global_default_virtual_product_on_hold_status';

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && !$product->is_virtual()) {
                $checkedStatus = 'global_default_on_hold_status';

                break;
            }
        }

        return (@get_option('tpay_settings_option_name')[$checkedStatus]) == 'completed' ? 'completed' : 'processing';
    }
}
