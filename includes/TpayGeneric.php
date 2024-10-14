<?php

namespace Tpay;

use WC_Order;

class TpayGeneric extends TpayGateways
{
    /** @var null|int */
    protected $channelId;

    public function __construct(string $id, ?int $channelId = null)
    {
        parent::__construct($id);

        $this->channelId = $channelId;

        add_filter('woocommerce_available_payment_gateways', [$this, 'unset_gateway']);
    }

    public function set_icon(string $icon)
    {
        $this->icon = $icon;
    }

    public function unset_gateway(array $gateways): array
    {
        $genericPayments = tpayOption('global_generic_payments');

        if ($this->channelId && !in_array($this->channelId, $genericPayments ?? [])) {
            unset($gateways[$this->id]);
        }

        return parent::unset_gateway($gateways);
    }

    public function init_form_fields()
    {
        parent::tpay_init_form_fields();
    }

    public function payment_fields()
    {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }

        $agreements = $this->gateway_helper->agreements_field();

        include plugin_dir_path(__FILE__).'../views/html/agreements.php';
    }

    public function process_payment($order_id): array
    {
        $this->crc = $this->createCRC($order_id);
        $order = new WC_Order($order_id);

        if (!$this->channelId) {
            $this->channelId = $this->request->get('tpay-channel');
            $this->id = "tpaygeneric-{$this->request->get('tpay-channel')}";
        }

        $this->set_payment_data($order, $this->channelId);
        $result = $this->process_transaction($order);

        if ('success' == $result['result']) {
            if ($errors_list = $this->gateway_helper->tpay_has_errors($result)) {
                $this->gateway_helper->tpay_logger(
                    'Nieudana próba płatności- zwrócone następujące błędy: '.implode(' ', $errors_list)
                );
                wc_add_notice(implode(' ', $errors_list), 'error');

                return [];
            }

            $order->set_transaction_id($result['transactionId']);
            $redirect = $result['transactionPaymentUrl'] ?: $this->get_return_url($order);
            $md5 = md5(
                $this->id_seller.$result['title'].$this->payment_data['amount'].$this->crc.$this->security_code
            );

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

        return [];
    }

    public function set_payment_data($order, $channelId)
    {
        $payer_data = $this->gateway_helper->payer_data($order, tpayOption('global_tax_id_meta_field_name'));
        $merchant_email = get_option('admin_email');

        if (tpayOption('global_merchant_email')) {
            $merchant_email = tpayOption('global_merchant_email');
        }

        $this->payment_data = [
            'description' => __('Order', 'tpay').' #'.$order->get_id(),
            'hiddenDescription' => $this->crc,
            'amount' => $order->get_total(),
            'payer' => $payer_data,
            'lang' => tpay_lang(),
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

        $this->payment_data['pay'] = [
            'channelId' => $channelId,
            'method' => 'pay_by_link',
        ];
    }
}
