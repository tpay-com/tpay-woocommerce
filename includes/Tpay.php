<?php

namespace Tpay;

use WC_Order;

class Tpay extends TpayGateways
{
    private $unset_banks = [];

    /** @var bool */
    private $hide_bank_selection;

    public function __construct()
    {
        parent::__construct(TPAYPBL_ID);
        $this->has_terms_checkbox = true;
        $this->hide_bank_selection = 'yes' === $this->tpay_get_option(['woocommerce_tpaypbl_settings', 'hide_bank_selection']);
    }

    public function init_form_fields()
    {
        parent::tpay_init_form_fields(true);
    }

    public function payment_fields()
    {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }

        $agreements = '';

        if ($this->has_terms_checkbox) {
            $agreements = $this->gateway_helper->agreements_field();
        }

        if ($this->hide_bank_selection) {
            include plugin_dir_path(__FILE__).'../views/html/agreements.php';

            return;
        }

        include plugin_dir_path(__FILE__).'../views/html/pbl.php';
    }

    public static function get_form_custom_order(): array
    {
        return [
            'custom_order' => [
                'title' => __('Custom order', 'tpay'),
                'type' => 'text',
                'description' => __('Custom order, separate payment methods with commas', 'tpay'),
                'placeholder' => __('Custom order, separate payment methods with commas', 'tpay'),
                'desc_tip' => true,
            ],
            'hide_bank_selection' => [
                'title' => __('Hide bank selection', 'tpay'),
                'type' => 'checkbox',
                'description' => __('Redirect to payment panel without choosing bank in advance', 'tpay'),
                'label' => __('Hide', 'tpay'),
                'desc_tip' => true,
            ],
        ];
    }

    public function process_payment($order_id)
    {
        $this->crc = $this->createCRC($order_id);
        $order = new WC_Order($order_id);

        if (!$this->hide_bank_selection) {
            $channelId = $this->request->get('tpay-channel-id');

            if (!$channelId || !is_numeric($channelId)) {
                $this->gateway_helper->tpay_logger('Nieudana próba płatności PBL- użytkownik nie wybrał banku');
                wc_add_notice(__('Select a bank', 'tpay'), 'error');

                return false;
            }
        }

        $this->set_payment_data($order, $channelId);
        $result = $this->process_transaction($order);

        if ('success' == $result['result']) {
            if ($errors_list = $this->gateway_helper->tpay_has_errors($result)) {
                $this->gateway_helper->tpay_logger('Nieudana próba płatności- zwrócone następujące błędy: '.implode(' ', $errors_list));
                wc_add_notice(implode(' ', $errors_list), 'error');

                return false;
            }

            $order->set_transaction_id($result['transactionId']);
            $redirect = $result['transactionPaymentUrl'] ?: $this->get_return_url($order);
            $md5 = md5($this->id_seller.$result['title'].$this->payment_data['amount'].$this->crc.$this->security_code);

            $order->update_meta_data('_transaction_id', $result['transactionId']);
            $order->update_meta_data('_md5_checksum', $md5);
            $order->update_meta_data('_crc', $this->crc);
            $order->update_meta_data('_payment_method', $this->id);

            $order->save();
            $this->gateway_helper->tpay_logger('Udane zamówienie, redirect na: '.$redirect);

            return [
                'result' => 'success',
                'redirect' => $redirect,
            ];
        }
        wc_add_notice(__('Payment error', 'tpay'), 'error');

        return false;
    }

    public function set_payment_data($order, $channelId)
    {
        $payer_data = $this->gateway_helper->payer_data($order);
        $merchant_email = get_option('admin_email');
        if (get_option('tpay_settings_option_name')['global_merchant_email']) {
            $merchant_email = get_option('tpay_settings_option_name')['global_merchant_email'];
        }
        $this->payment_data = [
            'description' => __('Order', 'tpay').' #'.$order->ID,
            'hiddenDescription' => $this->crc,
            'amount' => $order->get_total(),
            'payer' => $payer_data,
            'callbacks' => [
                'payerUrls' => [
                    'success' => $this->get_return_url($order),
                    'error' => wc_get_checkout_url(),
                ],
                'notification' => [
                    'url' => add_query_arg('wc-api', $this->gateway_data('api'), home_url('/')),
                    'email' => $merchant_email,
                ],
            ],
        ];

        if (!$this->hide_bank_selection) {
            $this->payment_data['pay'] = [
                'channelId' => (int) $channelId,
                'method' => 'pay_by_link',
            ];
        }
    }
}
