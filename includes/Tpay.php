<?php

namespace Tpay;

class Tpay extends TpayGateways
{
    private $unset_banks = [];

    function __construct()
    {
        parent::__construct(TPAYPBL_ID);
        $this->has_terms_checkbox = true;
    }

    function init_form_fields()
    {
        parent::tpay_init_form_fields(true);
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
        include plugin_dir_path(__FILE__) . '../views/html/pbl.php';

    }

    /**
     * @return array
     */
    public static function get_form_custom_order()
    {
        return [
            'custom_order' => [
                'title' => __('Custom order:', 'tpay'),
                'type' => 'text',
                'description' => __('Custom order, separate payment methods with commas', 'tpay'),
                'placeholder' => __('Custom order, separate payment methods with commas', 'tpay'),
                'desc_tip' => true
            ],
            'show_inactive_methods' => [
                'title' => __('Show inactive methods:', 'tpay'),
                'type' => 'checkbox',
                'description' => __('Show inactive payment methods as grayed out', 'tpay'),
                'label' => __('Show', 'tpay'),
                'desc_tip' => true
            ]
        ];
    }

    function process_payment($order_id)
    {
        $this->crc = $this->createCRC($order_id);
        $order = new \WC_Order($order_id);
        $groupID = $this->request->get('tpay-groupID');
        if (!$groupID || !is_numeric($groupID)) {
            $this->gateway_helper->tpay_logger('Nieudana próba płatności PBL- użytkownik nie wybrał banku');
            wc_add_notice(__('Select a bank', 'tpay'), 'error');
            return false;
        }
        $this->set_payment_data($order, $groupID);
        $result = $this->process_transaction($order);
        if ($result['result'] == 'success') {
            if ($errors_list = $this->gateway_helper->tpay_has_errors($result)) {
                $this->gateway_helper->tpay_logger('Nieudana próba płatności- zwrócone następujące błędy: ' . implode(' ', $errors_list));
                wc_add_notice(implode(' ', $errors_list), 'error');
                return false;
            } else {
                $redirect = $result['transactionPaymentUrl'] ? $result['transactionPaymentUrl'] : $this->get_return_url($order);
                $md5 = md5($this->id_seller . $result['title'] . $this->payment_data['amount'] . $this->crc . $this->security_code);
                update_post_meta($order->ID, '_transaction_id', $result['transactionId']);
                update_post_meta($order->ID, '_md5_checksum', $md5);
                update_post_meta($order->ID, '_crc', $this->crc);
                update_post_meta($order->ID, '_payment_method', $this->id);
                $this->gateway_helper->tpay_logger('Udane zamówienie, redirect na: ' . $redirect);
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
