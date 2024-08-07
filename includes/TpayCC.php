<?php

namespace Tpay;

use WC_Order;

class TpayCC extends TpayGateways
{
    const CHANNEL_ID = 53;

    public function __construct()
    {
        parent::__construct(TPAYCC_ID, TPAYCC);
        $this->has_terms_checkbox = true;
        $this->icon = apply_filters('woocommerce_tpay_icon', plugin_dir_url(__FILE__).'../views/img/card-visa-mc.svg');
    }

    public function payment_fields()
    {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }

        if ($this->has_terms_checkbox) {
            $agreements = $this->gateway_helper->agreements_field();
        }

        include plugin_dir_path(__FILE__).'../views/html/agreements.php';
    }

    public function process_payment($order_id)
    {
        $this->crc = $this->createCRC($order_id);
        $order = new WC_Order($order_id);
        $this->set_payment_data($order, self::CHANNEL_ID);
        $result = $this->process_transaction($order);

        if ('success' == $result['result']) {
            if ($errors_list = $this->gateway_helper->tpay_has_errors($result)) {
                $this->gateway_helper->tpay_logger('Nieudana próba płatności kartą na stronie Tpay- zwrócone następujące błędy: '.implode(' ', $errors_list));
                wc_add_notice(implode(' ', $errors_list), 'error');

                return false;
            }

            $redirect = $result['transactionPaymentUrl'] ?: $this->get_return_url($order);
            $order->set_transaction_id($result['transactionId']);
            $md5 = md5($this->id_seller.$result['title'].$this->payment_data['amount'].$this->crc.$this->security_code);
            $order->update_meta_data('md5_checksum', $md5);
            $order->update_meta_data('crc', $this->crc);
            $order->set_payment_method($this->id);

            $order->save();

            return [
                'result' => 'success',
                'redirect' => $redirect,
            ];
        }
        wc_add_notice(__('Payment error', 'tpay'), 'error');

        return false;
    }
}
