<?php

namespace Tpay;

use Error;
use WC_Order;

class TpayBlik extends TpayGateways
{
    const CHANNEL_ID = 64;

    private $agreements;
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

    /** @return array */
    public static function get_form_blik0()
    {
        return [
            'enable_blik0' => [
                'title' => __('Enable Blik lvl 0', 'tpay'),
                'type' => 'checkbox',
                'description' => __('Enable Blik lvl 0', 'tpay'),
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
            unset($_SESSION['tpay_session']);
            unset($_SESSION['tpay_attempts']);
            $redirect = $result['transactionPaymentUrl'] ?: $this->get_return_url($order);
            $order->set_transaction_id($result['transactionId']);
            $md5 = md5($this->id_seller.$result['title'].$this->payment_data['amount'].$this->crc.$this->security_code);
            $order->update_meta_data('_transaction_id', $result['transactionId']);
            $order->update_meta_data('_md5_checksum', $md5);
            $order->update_meta_data('_crc', $this->crc);
            $order->update_meta_data('_payment_method', $this->id);

            $order->save();

            return [
                'result' => 'success',
                'redirect' => $redirect,
            ];
        }
        if ($result['message']) {
            $message = $result['message'];
            $this->gateway_helper->tpay_logger('Nie udało się utworzyć transakcji dla zamówienia: '.$order->ID.'. Tpay zwrócił błąd: '.$message);
            $this->gateway_helper->tpay_logger('Zrzut odpowiedzi: '.print_r($result, true));
        } else {
            $message = __('Payment error', 'tpay');
            $this->gateway_helper->tpay_logger('Nie udało się utworzyć transakcji dla zamówienia: '.$order->ID.'. Tpay zwrócił nie zwrócił błędu');
        }
        wc_add_notice($message, 'error');

        return false;
    }

    public function process_transaction(WC_Order $order)
    {
        try {
            if (!$_SESSION['tpay_session']) {
                $transaction = $this->tpay_api()->transactions()->createTransactionWithInstantRedirection($this->payment_data);
                $_SESSION['tpay_session'] = $transaction;
                $_SESSION['tpay_attempts'] = 0;
                $this->gateway_helper->tpay_logger('Tworzenie nowej transakcji BLIK dla zamówienia (podejście pierwsze) zamówienie: '.$order->get_id());
            } else {
                $transaction = $_SESSION['tpay_session'];
                $tpay_status = $this->tpay_api()->transactions()->getTransactionById($transaction['transactionId']);
                $this->gateway_helper->tpay_logger('Pobranie transakcji BLIK z Tpay na podstawie trid, odpowiedź Tpay:');
                $this->gateway_helper->tpay_logger(print_r($transaction, 1));
                if ($tpay_status['payments']['attempts']) {
                    $_SESSION['tpay_attempts'] = count($tpay_status['payments']['attempts']);
                    if (count($tpay_status['payments']['attempts']) >= 4 || in_array(end($tpay_status['payments']['attempts'])['paymentErrorCode'], [101, 104])) {
                        $transaction = $this->tpay_api()->transactions()->createTransaction($this->payment_data);
                        $this->gateway_helper->tpay_logger('Transakcja "używana", wykorzystany limit nowych transakcji lub błędy 101/104 w BLIK, zrzut $transaction: ');
                        $this->gateway_helper->tpay_logger(print_r($transaction, true));
                        $_SESSION['tpay_session'] = $transaction;
                        $_SESSION['tpay_attempts'] = 0;
                    }
                }
            }
        } catch (Error $e) {
            $this->gateway_helper->tpay_logger('Nieudana próba utworzenia transakcji BLIK dla zamówienia '.$order->get_id());
            wc_add_notice($e->getMessage(), 'error');

            return false;
        }
        if ($this->blik0_enabled) {
            $md5 = md5($this->id_seller.$transaction['title'].$this->payment_data['amount'].$this->crc.$this->security_code);
            $order->update_meta_data('_md5_checksum', $md5);
            $order->update_meta_data('_crc', $this->crc);
            $order->update_meta_data('_payment_method', $this->id);
            $result = $this->tpay_api()->transactions()->createInstantPaymentByTransactionId($this->additional_payment_data, $transaction['transactionId']);

            if ('success' == $result['result']) {
                $stop = false;
                $i = 0;
                do {
                    $order_status = $order->get_status();
                    $correct = false;
                    $tpay_status = $this->tpay_api()->transactions()->getTransactionById($transaction['transactionId']);
                    $errors = 0;

                    foreach ($tpay_status['payments']['attempts'] as $error) {
                        if ('' != $error['paymentErrorCode']) {
                            $errors++;
                        }
                    }

                    update_option('tpay_status_'.time().'___'.$i, print_r($tpay_status, true));

                    if ('correct' == $tpay_status['status']) {
                        $correct = true;
                    }

                    if ('wc-processing' == $order_status || 60 == $i || $correct) {
                        $this->gateway_helper->tpay_logger('Udana transakcja BLIK, zrzut getTransactionById:');
                        $this->gateway_helper->tpay_logger(print_r($tpay_status, 1));
                        $stop = true;
                    }

                    if ($errors > $_SESSION['tpay_attempts'] && !$correct || 4 == $errors) {
                        $stop = true;
                        $result = [
                            'result' => 'error',
                            'message' => $this->get_blik_error(end($tpay_status['payments']['attempts'])['paymentErrorCode']),
                        ];
                        $_SESSION['tpay_attempts'] = count($tpay_status['payments']['attempts']);
                    }

                    sleep(1);
                    $i++;
                } while (!$stop);

                return $result;
            }

            return [
                'result' => 'error',
                'message' => $result['errors'][0]['errorMessage'],
            ];
        }
        if (isset($transaction['transactionPaymentUrl'])) {
            $this->gateway_helper->tpay_logger('Udane utworzenie transakcji BLIK z wyjściem do Tpay. Link do bramki: '.$transaction['transactionPaymentUrl']);

            return $transaction;
        }
            $this->gateway_helper->tpay_logger('Nieudane utworzenie transakcji BLIK, wiadomość Tpay: '.$transaction['message']);

        return [
            'result' => 'error',
            'message' => $transaction['message'],
        ];
    }

    protected function get_blik_error($error)
    {
        $errors = [
            61 => __('invalid BLIK code or alias data format', 'tpay'),
            62 => __('error connecting BLIK system', 'tpay'),
            63 => __('invalid BLIK six-digit code', 'tpay'),
            64 => __('can not pay with BLIK code or alias for non BLIK transaction', 'tpay'),
            65 => __('incorrect transaction status - should be pending', 'tpay'),
            66 => __('BLIK POS is not available', 'tpay'),
            82 => __('given alias is non-unique', 'tpay'),
            84 => __('given alias has not been registered or has been deregistered', 'tpay'),
            85 => __('given alias section is incorrect', 'tpay'),
            100 => __('BLIK other error', 'tpay'),
            101 => __('BLIK payment declined by user', 'tpay'),
            102 => __('BLIK system general error', 'tpay'),
            103 => __('BLIK insufficient funds / user authorization error', 'tpay'),
            104 => __('BLIK user or system timeout', 'tpay'),
        ];

        return $errors[$error];
    }

    private function blik0_is_active()
    {
        return (bool) ('yes' == @get_option('woocommerce_tpayblik_settings')['enable_blik0']);
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
                    $blik0 = str_replace('-', '', $blik0);

                    if (strlen($blik0) < 6) {
                        $this->gateway_helper->tpay_logger('Nieudana płatność BLIK, błąd: '.__('Blik code is too short', 'tpay'));
                        wc_add_notice(__('Blik code is too short', 'tpay'), 'error');

                        return false;
                    }

                    if (!is_numeric($blik0)) {
                        $this->gateway_helper->tpay_logger('Nieudana płatność BLIK, błąd: '.__('invalid BLIK six-digit code', 'tpay'));
                        wc_add_notice(__('invalid BLIK six-digit code', 'tpay'), 'error');

                        return false;
                    }

                    $this->additional_payment_data['blikPaymentData'] = ['blikToken' => $blik0];

                    if ($this->user_blik_alias && !$this->user_has_saved_blik_alias) {
                        $this->additional_payment_data['blikPaymentData']['aliases'] = [
                            'value' => $this->user_blik_alias,
                            'type' => 'UID',
                            'label' => get_bloginfo('name'),
                        ];
                    }
                } else {
                    $this->gateway_helper->tpay_logger('Nieudana płatność BLIK, błąd: '.__('Enter Blik code', 'tpay'));
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
        $blikStatus = $this->gateway_helper->user_blik_status();
        $this->user_blik_alias = $blikStatus[0];
        $this->user_has_saved_blik_alias = $blikStatus[1];
    }
}
