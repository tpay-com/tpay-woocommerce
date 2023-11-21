<?php

namespace Tpay;

class TpayInstallments extends TpayGateways
{
    const CHANNEL_ID = 49;

    function __construct()
    {
        parent::__construct(TPAYINSTALLMENTS_ID, TPAYINSTALLMENTS);
        $this->has_terms_checkbox = true;
        $this->icon = apply_filters('woocommerce_tpay_icon', 'https://secure.tpay.com/tpay/Transfer/web/img/groups/raty.png?4');
    }

    function init_form_fields()
    {
        parent::tpay_init_form_fields(false);
    }

    /**
     * @return void
     */
    public function payment_fields()
    {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }
        $agreements = '';
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

        if ($result['result'] == 'success') {
            if ($errors_list = $this->gateway_helper->tpay_has_errors($result)) {
                $this->gateway_helper->tpay_logger('Nieudana próba płatności ratalnej- zwrócone następujące błędy: ' . implode(' ', $errors_list));
                wc_add_notice(implode(' ', $errors_list), 'error');

                return false;
            } else {
                $redirect = $result['transactionPaymentUrl'] ? $result['transactionPaymentUrl'] : $this->get_return_url($order);
                $md5 = md5($this->id_seller . $result['title'] . $this->payment_data['amount'] . $this->crc . $this->security_code);
                update_post_meta($order->ID, '_transaction_id', $result['transactionId']);
                update_post_meta($order->ID, '_md5_checksum', $md5);
                update_post_meta($order->ID, '_crc', $this->crc);
                update_post_meta($order->ID, '_payment_method', $this->id);
                $this->gateway_helper->tpay_logger('Udane zamówienie, płatność ratalna, redirect na: ' . $redirect);

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
