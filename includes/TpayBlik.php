<?php

namespace Tpay;

use Error;
use WC_Order;

class TpayBlik extends TpayGateways
{
    const CHANNEL_ID = 64;

    private $blik0_enabled;
    private $user_blik_alias;
    private $user_has_saved_blik_alias;

    public function __construct()
    {
        parent::__construct(TPAYBLIK_ID, TPAYBLIK);
        $this->has_terms_checkbox = true;
        $this->icon = apply_filters('woocommerce_tpay_icon', plugin_dir_url(__FILE__).'../views/img/blik.png');
        $this->blik0_enabled = $this->blik0_is_active();
    }

    public function init_form_fields()
    {
        parent::tpay_init_form_fields(false, true);
    }

    public function isBlikZeroEnabled(): bool
    {
        return $this->blik0_enabled;
    }

    public function payment_fields()
    {
        $this->init_blik_user_info();

        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }

        $agreements = '';
        $alias = false;

        if ($this->has_terms_checkbox) {
            $agreements = $this->gateway_helper->agreements_field();
        }

        if ($this->user_has_saved_blik_alias) {
            $alias = true;
        }

        if ($this->blik0_enabled) {
            include plugin_dir_path(__FILE__).'../views/html/blik0.php';
        } else {
            include plugin_dir_path(__FILE__).'../views/html/agreements.php';
        }
    }

    public static function get_form_blik0(): array
    {
        return [
            'enable_blik0' => [
                'title' => __('Enable Blik lvl 0', 'tpay'),
                'type' => 'checkbox',
                'description' => __('Allows to enter blik code on the order page', 'tpay'),
                'label' => __('Enable', 'tpay'),
                'desc_tip' => true,
            ],
        ];
    }

    public function process_payment($order_id)
    {
        $this->crc = $this->createCRC($order_id);
        $order = new WC_Order($order_id);
        $this->set_payment_data($order, self::CHANNEL_ID);

        if (!$this->additional_payment_data() && $this->blik0_enabled) {
            return false;
        }

        $result = $this->process_transaction($order);

        if ('success' == $result['result']) {
            if ($errors_list = $this->gateway_helper->tpay_has_errors($result)) {
                wc_add_notice(implode(' ', $errors_list), 'error');

                return false;
            }

            $redirect = $result['transactionPaymentUrl'] ?: $this->get_return_url($order);
            $order->set_transaction_id($result['transactionId']);
            $md5 = md5(
                sprintf(
                    '%s%s%s%s%s',
                    $this->id_seller,
                    $result['title'],
                    $this->payment_data['amount'],
                    $this->crc,
                    $this->security_code
                )
            );
            $order->update_meta_data('md5_checksum', $md5);
            $order->update_meta_data('crc', $this->crc);
            $order->set_payment_method($this->id);

            if ($this->blik0_is_active()) {
                $order->update_meta_data('blik0', 'active');
            }

            $order->save();

            return [
                'result' => 'success',
                'redirect' => $this->blik0_is_active() ? $this->get_return_url($order) : $redirect,
            ];
        }

        if ($result['message']) {
            $message = $result['message'];
            $this->gateway_helper->tpay_logger(
                'Nie udało się utworzyć transakcji dla zamówienia: '.$order->get_id().'. Tpay zwrócił błąd: '.$message
            );
            $this->gateway_helper->tpay_logger('Zrzut odpowiedzi: '.print_r($result, true));
        } else {
            $message = __('Payment error', 'tpay');
            $this->gateway_helper->tpay_logger(
                'Nie udało się utworzyć transakcji dla zamówienia: '.$order->get_id().'. Tpay zwrócił nie zwrócił błędu'
            );
        }

        wc_add_notice($message, 'error');

        return ['result' => 'failure'];
    }

    public function process_transaction(WC_Order $order)
    {
        try {
            $transaction = $this->tpay_api()->transactions()->createTransactionWithInstantRedirection(
                $this->payment_data
            );
        } catch (Error $e) {
            $this->gateway_helper->tpay_logger(
                'Nieudana próba utworzenia transakcji BLIK dla zamówienia '.$order->get_id()
            );
            wc_add_notice($e->getMessage(), 'error');

            return false;
        }

        if ($this->blik0_is_active()) {
            $this->tpay_api()->transactions()->createInstantPaymentByTransactionId(
                $this->additional_payment_data,
                $transaction['transactionId']
            );

            return $transaction;
        }

        if (isset($transaction['transactionPaymentUrl'])) {
            return $transaction;
        }

        $this->gateway_helper->tpay_logger(
            'Nieudane utworzenie transakcji BLIK, wiadomość Tpay: '.$transaction['message']
        );

        return [
            'result' => 'error',
            'message' => $transaction['message'],
        ];
    }

    public function checkTransactionStatus(string $transactionId)
    {
        return $this->tpay_api()->transactions()->getTransactionById($transactionId);
    }

    private function blik0_is_active(): bool
    {
        return 'yes' == @get_option('woocommerce_tpayblik_settings')['enable_blik0'];
    }

    private function additional_payment_data()
    {
        $this->init_blik_user_info();

        if ($this->blik0_enabled) {
            $this->additional_payment_data = [
                'channelId' => $this->payment_data['pay']['channelId'],
                'method' => $this->payment_data['pay']['method'],
                'blikPaymentData' => [
                    'type' => 0,
                ],
            ];

            if ('code' == $this->request->get('blik-type')) {
                if ($blik0 = $this->request->get('blik0')) {
                    $this->additional_payment_data['blikPaymentData'] = ['blikToken' => $blik0];

                    if ($this->user_blik_alias && !$this->user_has_saved_blik_alias) {
                        $this->additional_payment_data['blikPaymentData']['aliases'] = [
                            'value' => $this->user_blik_alias,
                            'type' => 'UID',
                            'label' => get_bloginfo('name'),
                        ];
                    }
                } else {
                    $this->gateway_helper->tpay_logger(
                        'Nieudana płatność BLIK, błąd: '.__('Enter Blik code', 'tpay')
                    );
                    wc_add_notice(__('Enter Blik code', 'tpay'), 'error');

                    return false;
                }
            } elseif ('alias' == $this->request->get('blik-type') && $this->user_has_saved_blik_alias) {
                $this->additional_payment_data['blikPaymentData']['aliases'] = [
                    'value' => $this->user_blik_alias,
                    'type' => 'UID',
                    'label' => get_bloginfo('name'),
                ];
            } else {
                $this->gateway_helper->tpay_logger('Nieudana płatność BLIK, błąd: '.__('Payment error', 'tpay'));
                wc_add_notice(__('Payment error', 'tpay'), 'error');

                return false;
            }

            return true;
        }
    }

    private function init_blik_user_info()
    {
        [$this->user_blik_alias, $this->user_has_saved_blik_alias] = $this->gateway_helper->user_blik_status();
    }
}
