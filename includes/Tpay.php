<?php

namespace Tpay;

class Tpay extends TpayGateways
{
    private $unset_banks = [];

    /** @var bool */
    private $hide_bank_selection;

    function __construct()
    {
        parent::__construct(TPAYPBL_ID);
        $this->has_terms_checkbox = true;
        $this->hide_bank_selection = $this->get_tpay_option(['woocommerce_tpaypbl_settings', 'hide_bank_selection']) === 'yes';
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
        if ($this->hide_bank_selection) {
            return;
        }

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
                'title' => __('Custom order', 'tpay'),
                'type' => 'text',
                'description' => __('Custom order, separate payment methods with commas', 'tpay'),
                'placeholder' => __('Custom order, separate payment methods with commas', 'tpay'),
                'desc_tip' => true
            ],
            'show_inactive_methods' => [
                'title' => __('Show inactive methods', 'tpay'),
                'type' => 'checkbox',
                'description' => __('Show inactive payment methods as grayed out', 'tpay'),
                'label' => __('Show', 'tpay'),
                'desc_tip' => true
            ],
            'hide_bank_selection' => [
                'title' => __('Hide bank selection', 'tpay'),
                'type' => 'checkbox',
                'description' => __('Redirect to payment panel without choosing bank in advance', 'tpay'),
                'label' => __('Hide', 'tpay'),
                'desc_tip' => true
            ]
        ];
    }

    function process_payment($order_id)
    {
        $this->crc = $this->createCRC($order_id);
        $order = new \WC_Order($order_id);

        if (!$this->hide_bank_selection) {
            $groupID = $this->request->get('tpay-groupID');

            if (!$groupID || !is_numeric($groupID)) {
                $this->gateway_helper->tpay_logger('Nieudana próba płatności PBL- użytkownik nie wybrał banku');
                wc_add_notice(__('Select a bank', 'tpay'), 'error');
                return false;
            }
        }

        $this->set_payment_data($order, $groupID ?? null);
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

    public function set_payment_data($order, $groupID)
    {
        $payer_data = $this->gateway_helper->payer_data($order);
        $merchant_email = get_option('admin_email');
        if (get_option('tpay_settings_option_name')['global_merchant_email']) {
            $merchant_email = get_option('tpay_settings_option_name')['global_merchant_email'];
        }
        $this->payment_data = [
            'description' => __('Order', 'tpay') . ' #' . $order->ID,
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
                ]
            ]
        ];

        if (!$this->hide_bank_selection) {
            $this->payment_data['pay'] = [
                'groupId' => (int) $groupID,
                'method' => 'pay_by_link'
            ];
        }
    }
}
