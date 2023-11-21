<?php

namespace Tpay;

class TpayCC extends TpayGateways
{
    const CHANNEL_ID = 53;

    function __construct()
    {
        parent::__construct(TPAYCC_ID, TPAYCC);
        $this->has_terms_checkbox = true;
        $this->icon = apply_filters('woocommerce_tpay_icon', plugin_dir_url(__FILE__) . '../views/img/card-visa-mc.svg');
    }

    /**
     * @return void
     */
    public function payment_fields()
    {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }

        if ($this->has_terms_checkbox) {
            $agreements = $this->gateway_helper->agreements_field();
        }

        include plugin_dir_path(__FILE__) . '../views/html/agreements.php';
    }

    function process_payment($order_id)
    {
        $this->crc = $this->createCRC($order_id);
        $order = new \WC_Order($order_id);
        $this->set_payment_data($order, self::CHANNEL_ID);
        $result = $this->process_transaction($order);
        update_option('PROCESS_TRANSACTION' . time(), print_r($result, true));

        if ($result['result'] == 'success') {
            if ($errors_list = $this->gateway_helper->tpay_has_errors($result)) {
                $this->gateway_helper->tpay_logger('Nieudana próba płatności kartą na stronie Tpay- zwrócone następujące błędy: ' . implode(' ', $errors_list));
                wc_add_notice(implode(' ', $errors_list), 'error');

                return false;
            } else {
                $redirect = $result['transactionPaymentUrl'] ?: $this->get_return_url($order);
                $md5 = md5($this->id_seller . $result['title'] . $this->payment_data['amount'] . $this->crc . $this->security_code);
                update_post_meta($order->ID, '_transaction_id', $result['transactionId']);
                update_post_meta($order->ID, '_md5_checksum', $md5);
                update_post_meta($order->ID, '_crc', $this->crc);
                update_post_meta($order->ID, '_payment_method', $this->id);
                $this->gateway_helper->tpay_logger('Udane zamówienie, płatność kartą na stronie Tpay, redirect na: ' . $redirect);

                return [
                    'result' => 'success',
                    'redirect' => $redirect,
                ];
            }
        } else {
            wc_add_notice(__('Payment error', 'tpay'), 'error');

            return false;
        }
    }
}
