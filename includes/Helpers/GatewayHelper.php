<?php

namespace Tpay\Helpers;

use WC_Order;

class GatewayHelper
{
    const CONDITION_PL = 'https://secure.tpay.com/regulamin.pdf';
    const CONDITION_EN = 'https://tpay.com/user/assets/files_for_download/payment-terms-and-conditions.pdf';
    const PRIVACY_PL = 'https://tpay.com/user/assets/files_for_download/klauzula-informacyjna-platnik.pdf';
    const PRIVACY_EN = 'https://tpay.com/user/assets/files_for_download/information-clause-payer.pdf';

    public $additional_payment_data;
    public $crc;
    public $card_helper;

    public function __construct()
    {
        $this->card_helper = new CardHelper();
    }

    public function set_additional_payment_data($gateway_id, $payment_data, $crc, $post_data = null)
    {
        $this->crc = $crc;
        $result = [
            'groupId' => $payment_data['pay']['groupId'],
            'method' => $payment_data['pay']['method'],
        ];

        switch ($gateway_id) {
            case TPAYBLIK_ID:
                if ('yes' == get_option('woocommerce_tpayblik_settings')['enable_blik0']) {
                    $result = $result + $this->blik_payment_data($post_data);
                }
                break;
            case TPAYSF_ID:
                $result = $result + $this->sf_payment_data($post_data);
                break;
            default:
                $result = true;
                break;
        }

        return $result;
    }

    public function tpay_logger($log)
    {
        $context = ['source' => 'tpay'];
        $logger = wc_get_logger();
        $logger->debug($log."\r\n-----------\r\n", $context);
    }

    /** @return string */
    public function get_condition_url()
    {
        return 'pl_PL' === get_locale() ? self::CONDITION_PL : self::CONDITION_EN;
    }

    /** @return string */
    public function get_privacy_policy_url()
    {
        return 'pl_PL' === get_locale() ? self::PRIVACY_PL : self::PRIVACY_EN;
    }

    /** @return string */
    public function agreements_field()
    {
        return sprintf(
            '<div class="tpay-accept-conditions">
                       <p>%s <a href="%s" target="_blank">%s</a></p>
                       <p style="display: none">%s <br />
                       <a href="%s" target="_blank">%s</a>
                       </p>
                       <a href="#" class="agreement_text_scroller" data-less="%s" data-more="%s">%s</a>
                       </div>',
            __('By paying,', 'tpay'),
            $this->get_condition_url(),
            __('you accept the terms and conditions', 'tpay'),
            __('The administrator of the personal data is Krajowy Integrator Płatności S.A., headquartered in Poznań.', 'tpay'),
            $this->get_privacy_policy_url(),
            __('Read the full text.', 'tpay'),
            __('Read less', 'tpay'),
            __('Read more', 'tpay'),
            __('Read more', 'tpay')
        );
    }

    public function get_order_by_transaction_crc($crc): ?WC_Order
    {
        $order = wc_get_orders(['meta_key' => 'crc', 'meta_value' => $crc]);

        if (null === $order) {
            $order = wc_get_orders(['meta_key' => '_crc', 'meta_value' => $crc]);
        }

        if (count($order) > 1) {
            $this->tpay_logger('Pobrano zbyt wiele zamówień. Liczba zamówień: '.count($order));
        }

        return $order[0] ?? null;
    }

    public function user_blik_status(): array
    {
        if (!get_current_user_id()) {
            $user_blik_alias = false;
            $user_has_saved_blik_alias = false;
        } else {
            $user_blik_alias = WP_TPAY_BLIK_PREFIX.'_'.get_current_user_id();
            $user_has_saved_blik_alias = (bool) get_user_meta(get_current_user_id(), 'tpay_alias_blik', true);
        }

        return [$user_blik_alias, $user_has_saved_blik_alias];
    }

    public function payer_data($order, $taxIdField = null): array
    {
        $paymentData = [
            'email' => $order->get_billing_email(),
            'name' => $order->get_billing_first_name().' '.$order->get_billing_last_name(),
        ];

        if ($order->get_billing_postcode()) {
            $paymentData['code'] = $order->get_billing_postcode();
            $paymentData['address'] = $order->get_billing_address_1().', '.$order->get_billing_address_2();
            $paymentData['city'] = $order->get_billing_city();
            $paymentData['country'] = $order->get_billing_country();
            $paymentData['phone'] = $order->get_billing_phone();
        }

        if ($taxIdField) {
            $taxId = $order->get_meta($taxIdField);

            if (!$taxId && function_exists('wpdesk_get_order_meta')) {
                $taxId = wpdesk_get_order_meta($order, '_'.$taxIdField, true);
            }

            if ($taxId) {
                $paymentData['taxId'] = $taxId;
            }
        }

        return array_filter($paymentData);
    }

    public function tpay_has_errors($response)
    {
        if ($errors = @$response['payments']['errors']) {
            $errors_list = [];
            foreach ($errors as $error) {
                $errors_list[] = $error['errorMessage'];
            }

            return $errors_list;
        }

        return false;
    }

    private function sf_payment_data($post_data)
    {
        if ($card_id = $post_data['saved-card']) {
            $card = CardHelper::get_card_by_id($card_id);
            if ($card) {
                $this->additional_payment_data['cardPaymentData'] = [
                    'token' => $card['token'],
                ];
            }
        } elseif ($carddata = $post_data['carddata']) {
            $this->additional_payment_data['cardPaymentData'] = [
                'card' => $carddata,
            ];
            if ($post_data['save-card']) {
                $save_card = [
                    'card_vendor' => $post_data['card_vendor'],
                    'card_hash' => $post_data['card_hash'],
                    'card_short_code' => $post_data['card_short_code'],
                    'crc' => $this->crc,
                ];
                if ($this->card_helper->save_card($save_card)) {
                    $this->additional_payment_data['cardPaymentData']['save'] = true;
                }
            }
        }

        return $this->additional_payment_data;
    }

    private function blik_payment_data($post_data): bool
    {
        if ($post_data['blik0']) {
            $blik0 = str_replace('-', '', $post_data['blik0']);
            if ($post_data['user_blik_alias']) {
                $this->additional_payment_data['blikPaymentData'] = [
                    'blikToken' => $blik0,
                    'type' => 0,
                    'aliases' => [
                        'value' => $post_data['user_blik_alias'],
                        'type' => 'UID',
                        'label' => get_bloginfo('name'),
                    ],
                ];
            } else {
                $this->additional_payment_data['blikPaymentData'] = [
                    'blikToken' => $blik0,
                    'type' => 0,
                ];
            }
        } else {
            if (!$post_data['user_has_saved_blik_alias']) {
                wc_add_notice(__('Enter Blik code', 'tpay'), 'error');

                return false;
            }

            $this->additional_payment_data['blikPaymentData'] = [
                'aliases' => [
                    'value' => $post_data['user_blik_alias'],
                    'type' => 'UID',
                    'label' => get_bloginfo('name'),
                ],
                'type' => 0,
            ];
        }

        return $this->additional_payment_data;
    }
}
