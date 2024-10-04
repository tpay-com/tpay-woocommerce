<?php

namespace Tpay;

use Tpay\Api\Dtos\Channel;
use WC_Order;

class PekaoInstallments extends TpayGateways
{
    public function __construct()
    {
        parent::__construct(TPAYPEKAOINSTALLMENTS_ID, TPAYPEKAOINSTALLMENTS);
        $this->has_terms_checkbox = true;
        $this->icon = apply_filters('woocommerce_tpay_icon', plugin_dir_url(__FILE__).'../views/img/raty_pekao.png');
    }

    public static function get_form_installments(): array
    {
        return [
            'tpay_settings_installments_merchant_id' => [
                'title' => __('Merchant ID', 'tpay'),
                'type' => 'number',
                'description' => __('When the installment simulator is enabled, the merchant ID field must be filled in', 'tpay'),
                'label' => __('Show', 'tpay'),
                'desc_tip' => true,
            ],
            'tpay_settings_installments_product' => [
                'title' => __('Installments simulator on product page', 'tpay'),
                'type' => 'checkbox',
                'description' => __('Enable installments simulator on product page', 'tpay'),
                'label' => __('Show', 'tpay'),
                'desc_tip' => true,
            ],
            'tpay_settings_installments_cart' => [
                'title' => __('Installments simulator on cart page', 'tpay'),
                'type' => 'checkbox',
                'description' => __('Enable installments simulator on cart page', 'tpay'),
                'label' => __('Show', 'tpay'),
                'desc_tip' => true,
            ],
            'tpay_settings_installments_checkout' => [
                'title' => __('Installments simulator on checkout page', 'tpay'),
                'type' => 'checkbox',
                'description' => __('Enable installments simulator on checkout page', 'tpay'),
                'label' => __('Show', 'tpay'),
                'desc_tip' => true,
            ],
        ];
    }

    public function init_form_fields()
    {
        parent::tpay_init_form_fields(false, false, false, true);
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

        $channels = $this->channels();
        $cartTotal = $this->getCartTotal();

        $list = $this->filter_out_constraints(array_filter($channels, function (Channel $channel) {
            foreach ($channel->groups as $group) {
                if (TPAYPEKAOINSTALLMENTS !== $group->id) {
                    return false;
                }
            }

            return !(empty($channel->groups));
        }));

        $agreements = '';

        if ($this->has_terms_checkbox) {
            $agreements = $this->gateway_helper->agreements_field();
        }

        include plugin_dir_path(__FILE__).'../views/html/pekao.php';
    }

    public function process_payment($order_id)
    {
        $this->crc = $this->createCRC($order_id);
        $order = new WC_Order($order_id);
        $groupID = TPAYPEKAOINSTALLMENTS;
        $this->set_payment_data($order, $groupID);
        $channelId = $this->request->get('tpay-channel-id');

        if ($channelId) {
            $this->payment_data['pay'] = ['channelId' => (int) $channelId];
        }

        $result = $this->process_transaction($order);

        if ('success' == $result['result']) {
            if ($errors_list = $this->gateway_helper->tpay_has_errors($result)) {
                $this->gateway_helper->tpay_logger('Nieudana próba płatności ratalnej- zwrócone następujące błędy: '.implode(' ', $errors_list));
                wc_add_notice(implode(' ', $errors_list), 'error');

                return false;
            }

            $redirect = $result['transactionPaymentUrl'] ?: $this->get_return_url($order);
            $order->set_transaction_id($result['transactionId']);
            $order->set_payment_method($this->id);
            $md5 = md5($this->id_seller.$result['title'].$this->payment_data['amount'].$this->crc.$this->security_code);
            $order->update_meta_data('md5_checksum', $md5);
            $order->update_meta_data('crc', $this->crc);

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
