<?php

namespace Tpay\Ipn;

use Exception;
use Tpay\Helpers;
use tpaySDK\Webhook\JWSVerifiedPaymentNotification;
use WC_Order;

class UpdateOrderStatus implements IpnInterface
{
    private $gateway_helper;
    private $card_helper;

    public function __construct()
    {
        $this->gateway_helper = new Helpers\GatewayHelper();
        $this->card_helper = new Helpers\CardHelper();
    }

    public function parseNotification($response)
    {
        $order = $this->gateway_helper->get_order_by_transaction_crc($response['tr_crc']);

        if (!$order) {
            echo 'FALSE';
            exit();
        }

        $order_method = $order->get_payment_method();
        $class = TPAY_CLASSMAP[$order_method];
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
        $status = (@get_option(
            'tpay_settings_option_name'
        )['global_default_on_hold_status']) == 'completed' ? 'completed' : 'processing';
        $order->update_status($status, sprintf('%s : %s. ', __('CRC number in Tpay', 'tpay'), $response['tr_crc']));
        $order->payment_complete($order->get_transaction_id());
        $this->gateway_helper->tpay_logger(
            'Przyjęcie płatności dla zamówienia: '.$order->get_id().', zrzut odpowiedzi:'
        );
        $this->gateway_helper->tpay_logger(print_r($response, 1));

        if (isset($response['card_token'])) {
            $this->gateway_helper->tpay_logger(
                'Komunikat z bramki z tokenem karty, dotyczy zamówienia: '.$order->get_id().', zrzut odpowiedzi:'
            );
            $this->gateway_helper->tpay_logger(print_r($response, 1));
            $this->saveUserCard($response);
        }

        header('HTTP/1.1 200 OK');
        echo 'TRUE';
        exit();
    }

    public function orderIsNotComplete($response)
    {
        $this->gateway_helper->tpay_logger(
            'Przyjęto zgłoszenie z bramki Tpay, że płatność za zamówienie nie powiodło się. Zrzut odpowiedzi:'
        );
        $this->gateway_helper->tpay_logger(print_r($response, 1));
        header('HTTP/1.1 200 OK');
        echo 'FALSE';
        exit();
    }

    public function saveUserCard($response)
    {
        $crc = $response['tr_crc'];

        if ($order = $this->gateway_helper->get_order_by_transaction_crc($crc)) {
            $this->gateway_helper->tpay_logger('Zapisanie tokenu karty');
            $this->card_helper->update_card_token(
                $order->get_customer_id(),
                $crc,
                $response['card_token'],
                $order->get_id()
            );
        }
    }
}
