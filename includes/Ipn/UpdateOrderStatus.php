<?php

namespace Tpay\Ipn;

use Tpay\Helpers;
use tpaySDK\Webhook\JWSVerifiedPaymentNotification;

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
        $order_id = $this->gateway_helper->get_order_by_transaction_crc($response['tr_crc']);
        if (!$order_id) {
            echo 'FALSE';
            die();
        }

        $order_method = get_post_meta($order_id, '_payment_method', true);
        $class = TPAY_CLASSMAP[$order_method];
        $gateway = new $class();
        $config = (new Helpers\ConfigProvider())->get_config($gateway);

        $isProd = (@get_option('tpay_settings_option_name')['global_tpay_environment']) != 'sandbox';
        try {
            $NotificationWebhook = new JWSVerifiedPaymentNotification(
                $config['security_code'],
                $isProd
            );
            $notification = $NotificationWebhook->getNotification();
            $notificationData = $notification->getNotificationAssociative();
        } catch (\Exception $e) {
            $this->gateway_helper->tpay_logger($e->getMessage());
            echo 'FALSE';
            die();
        }
        switch ($notificationData['tr_status']) {
            case 'TRUE';
            case 'PAID';
                $this->orderIsComplete($order_id, $notificationData);
                break;
            case 'CHARGEBACK';
                $order = new \WC_Order($order_id);
                $order->update_status('refunded');
                $this->orderIsRefunded($notificationData);
                break;

            case 'FALSE';
                $this->orderIsNotComplete($notificationData);
                break;
        }
    }

    function orderIsRefunded($response)
    {
        header("HTTP/1.1 200 OK");
        echo 'TRUE';
        die();
    }

    function orderIsComplete($order_id, $response)
    {
        $status = (@get_option('tpay_settings_option_name')['global_default_on_hold_status']) == 'completed' ? 'completed' : 'processing';
        $order = new \WC_Order($order_id);
        $order->update_status($status);
        $order->payment_complete($order->get_transaction_id());
        $this->gateway_helper->tpay_logger('Przyjęcie płatności dla zamówienia: ' . $order_id . ', zrzut odpowiedzi:');
        $this->gateway_helper->tpay_logger(print_r($response, 1));
        if (isset($response['card_token'])) {
            $this->gateway_helper->tpay_logger('Komunikat z bramki z tokenem karty, dotyczy zamówienia: ' . $order_id . ', zrzut odpowiedzi:');
            $this->gateway_helper->tpay_logger(print_r($response, 1));
            $this->saveUserCard($response);
        }
        header("HTTP/1.1 200 OK");
        echo 'TRUE';
        die();
    }

    function orderIsNotComplete($response)
    {
        $this->gateway_helper->tpay_logger('Przyjęto zgłoszenie z bramki Tpay, że płatność za zamówienie nie powiodło się. Zrzut odpowiedzi:');
        $this->gateway_helper->tpay_logger(print_r($response, 1));
        header("HTTP/1.1 200 OK");
        echo 'FALSE';
        die();
    }

    function saveUserCard($response)
    {
        $crc = $response['tr_crc'];
        if ($order_exists = $this->gateway_helper->get_order_by_transaction_crc($crc)) {
            $customer = get_post_meta($order_exists, '_customer_user', true);
            $this->gateway_helper->tpay_logger('Zapisanie tokenu karty');
            $this->card_helper->update_card_token($customer, $crc, $response['card_token'], $order_exists);
        }
    }
}