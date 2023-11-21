<?php

namespace Tpay;

class TpaySF extends TpayGateways
{
    public $card_helper;

    function __construct()
    {
        parent::__construct(TPAYSF_ID, TPAYSF);
        $this->card_helper = new Helpers\CardHelper();
        $this->has_terms_checkbox = true;
        $this->icon = apply_filters('woocommerce_tpay_icon', plugin_dir_url(__FILE__) . '../views/img/card-visa-mc.svg');
        $this->setSubscriptionsSupport();
        $list = $this->getBanksList(false);
        $has_sf = false;

        foreach ($list as $item) {
            if ($item['id'] == TPAYSF) {
                $has_sf = true;
            }
        }

        if (!$has_sf || get_woocommerce_currency() != 'PLN') {
            add_filter('woocommerce_available_payment_gateways', [$this, 'unset_gateway']);
        }
    }

    function init_form_fields()
    {
        parent::tpay_init_form_fields(false, false, true);
    }

    public function scheduled_subscription_payment($chargeAmount, $order)
    {
        $user_id = $order->get_user_id();

        if ($user_id) {
            $cards = $this->userCards($user_id);

            foreach ($cards as $card) {
                $use_card = $card;
            }

            $payer_data = $this->gateway_helper->payer_data($order);
            $payment_data = [
                'description' => __('Order', 'tpay') . ' #' . $order->ID,
                'amount' => $chargeAmount,
                'pay' => [
                    'channelId' => TpayCC::CHANNEL_ID,
                ],
                'payer' => $payer_data,
                'callbacks' => [
                    'payerUrls' => [
                        'success' => $this->get_return_url($order),
                        'error' => wc_get_checkout_url(),
                    ],
                    'notification' => [
                        'url' => add_query_arg('wc-api', $this->gateway_data('api'), home_url('/')),
                        'email' => $payer_data['email'],
                    ]
                ],
            ];

            $log = "Uruchomienie płatności za subskrypcję";
            $this->gateway_helper->tpay_logger($log);


            $paydata = [
                'channelId' => TpayCC::CHANNEL_ID,
                'method' => 'sale',
                'cardPaymentData' => [
                    'token' => $use_card['token']
                ]
            ];
            $i = 0;
            $stop = false;
            do {
                $crc = $this->createCRC($order->get_id());
                $payment_data['hiddenDescription'] = $crc;

                try {
                    $transaction = $this->tpay_api()->Transactions->createTransactionWithInstantRedirection($payment_data);
                } catch (\Error $e) {
                    $this->gateway_helper->tpay_logger('Nieudana próba utworzenia transakcji kartą dla zamówienia ' . $order->ID);

                    return false;
                }

                $md5 = md5($this->id_seller . $transaction['title'] . $this->payment_data['amount'] . $crc . $this->security_code);
                $result = $this->tpay_api()->Transactions->createInstantPaymentByTransactionId($paydata, $transaction['transactionId']);

                if ($result['status'] == 'correct' || $i >= 1) {
                    $stop = true;
                }

                sleep(1);
                $i++;
                update_option('CREATE_PAYMENT' . time() . '__SUBS_TRY_' . $i, print_r($result, true));
            } while (!$stop);
            if ($result['result'] == 'success' && $result['status'] == 'correct') {
                $md5 = md5($this->id_seller . $result['title'] . $payment_data['amount'] . $payment_data['hiddenDescription'] . $this->security_code);
                update_post_meta($order->ID, '_transaction_id', $result['transactionId']);
                update_post_meta($order->ID, '_md5_checksum', $md5);
                update_post_meta($order->ID, '_crc', $payment_data['hiddenDescription']);
                update_post_meta($order->ID, '_payment_method', $this->id);
                $order->payment_complete($result['transactionId']);
                $order->update_status('completed');
                \WC_Subscriptions_Manager::process_subscription_payments_on_order($order);

                return true;
            } else {
                $order->add_order_note(__('Nieudana płatność kartą'));
                $this->gateway_helper->tpay_logger('Nieudane odnowienie subskrypcji w zamówieniu ' . $order->get_id());
                \WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($order);
            }
        } else {
            $order->add_order_note(__('Nieudana płatność kartą'));
            $this->gateway_helper->tpay_logger('Brak user id w nowym zamówieniu');
            \WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($order);
        }

        return false;
    }

    private function setSubscriptionsSupport()
    {
        $subscriptionsSupport = array(
            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'subscription_payment_method_change',
            'subscription_payment_method_change_customer',
            'subscription_payment_method_change_admin',
            'multiple_subscriptions',
        );

        if (class_exists('WC_Subscriptions', false)) {
            $this->supports = array_merge($this->supports, $subscriptionsSupport);
            add_action(
                'woocommerce_scheduled_subscription_payment_' . $this->id,
                array($this, 'scheduled_subscription_payment'),
                10,
                2
            );
        }
    }

    private function userCards($user_id = null)
    {
        if (!get_current_user_id() && !$user_id) return false;

        $result = [];
        global $wpdb;
        $table = $wpdb->prefix . 'tpay_cards';

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $sql = $wpdb->prepare('select id, vendor, short_code, token from ' . $table . ' where `user_id` = %d and token is not null', $user_id);
        $cards = $wpdb->get_results($sql);

        if ($cards) {
            foreach ($cards as $card) {
                $result[$card->id] = [
                    'vendor' => $card->vendor,
                    'short_code' => $card->short_code,
                    'token' => $card->token,
                ];
            }
        }

        return $result;
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

        $cards = $this->userCards();
        include plugin_dir_path(__FILE__) . '../views/html/sf.php';

    }

    /**
     * @return array
     */
    public static function get_form_sf()
    {
        $fields = [];
        $fields['sf_rsa'] = [
            'title' => __('RSA key', 'tpay'),
            'type' => 'text',
            'class' => 'sf-rsa-global',
            'description' => __('RSA key', 'tpay'),
            'label' => __('RSA key', 'tpay'),
            'desc_tip' => true
        ];
        $fields['mid_number'] = [
            'title' => __('MID number', 'tpay'),
            'description' => __('MID number', 'tpay'),
            'type' => 'select',
            'class' => 'mid-selector',
            'options' => [
                1 => 1,
                2 => 2,
                3 => 3,
                4 => 4,
                5 => 5,
                6 => 6,
                7 => 7,
                8 => 8,
                9 => 9,
                10 => 10,
            ]
        ];
        for ($i = 1; $i <= 10; $i++) {
            $fields['security_code_' . $i] = [
                'title' => __('Secret key (in notifications)', 'tpay'),
                'description' => __('The security code for your tpay.com account.', 'tpay'),
                'class' => 'mid-field mid-number-' . $i
            ];
            $fields['api_key' . $i] = [
                'title' => __('Client ID', 'tpay'),
                'description' => __('API key generated in tpay.com payment recipient\'s panel.', 'tpay'),
                'class' => 'mid-field mid-number-' . $i
            ];
            $fields['api_key_password' . $i] = [
                'title' => __('API key password', 'tpay'),
                'description' => __('API key password', 'tpay'),
                'class' => 'mid-field mid-number-' . $i
            ];
            $fields['sf_rsa' . $i] = [
                'title' => __('RSA key', 'tpay'),
                'description' => __('RSA key', 'tpay'),
                'class' => 'mid-field mid-number-' . $i
            ];
            $fields['sf_rsa' . $i] = [
                'title' => __('RSA key', 'tpay'),
                'description' => __('RSA key', 'tpay'),
                'class' => 'mid-field mid-number-' . $i
            ];
            $fields['middomain' . $i] = [
                'title' => __('MID domain', 'tpay'),
                'description' => __('MID domain', 'tpay'),
                'type' => 'url',
                'class' => 'mid-field mid-number-' . $i,
            ];
        }

        return $fields;
    }

    private function additional_payment_data($order_id)
    {
        $this->additional_payment_data['channelId'] = $this->payment_data['pay']['channelId'];
        $this->additional_payment_data['method'] = $this->payment_data['pay']['method'];

        if ($card_id = $this->request->get('saved-card')) {
            $card = $this->card_helper->get_card_by_id($card_id);

            if ($card) {
                $this->additional_payment_data['cardPaymentData'] = ['token' => $card['token']];
            }
        } elseif ($carddata = $this->request->get('carddata')) {
            $this->additional_payment_data['cardPaymentData'] = ['card' => $carddata];

            if ($this->request->get('save-card')) {
                $save_card = [
                    'card_vendor' => $this->request->get('card_vendor'),
                    'card_hash' => $this->request->get('card_hash'),
                    'card_short_code' => $this->request->get('card_short_code'),
                    'crc' => $this->crc,
                ];

                if ($this->card_helper->save_card($save_card)) {
                    $this->additional_payment_data['cardPaymentData']['save'] = true;
                }
            }
        } else {
            $this->gateway_helper->tpay_logger('Nieudana próba płatności kartą na stronie sklepu- niepoprawne dane karty');
            wc_add_notice(__('Enter the correct card details', 'tpay'), 'error');

            return false;
        }

        if (class_exists('WC_Subscriptions_Order', false) && \WC_Subscriptions_Order::order_contains_subscription($order_id)) {
            if (!$card_id && !$this->request->get('save-card')) {
                $this->gateway_helper->tpay_logger('Nieudana próba uruchomienia subskrypcji- użytkownik nie zaznaczył chęci zapisania karty.');
                wc_add_notice(__('In order to purchase a subscription service, you must agree to save the card', 'tpay'), 'error');

                return false;
            }
        }
        return true;
    }

    function process_payment($order_id)
    {
        $this->crc = $this->createCRC($order_id);
        $order = new \WC_Order($order_id);
        $user_id = $order->get_user_id();
        $this->set_payment_data($order, TpayCC::CHANNEL_ID);

        if (!$this->additional_payment_data($order_id)) {
            return false;
        }

        $result = $this->process_transaction($order);

        if ($result['result'] == 'success') {
            if ($errors_list = $this->gateway_helper->tpay_has_errors($result)) {
                $this->gateway_helper->tpay_logger('Nieudana próba płatności kartą na stronie sklepu- zwrócone następujące błędy: ' . implode(' ', $errors_list));
                wc_add_notice(implode(' ', $errors_list), 'error');

                return false;
            } else {
                $redirect = $result['transactionPaymentUrl'] ?: $this->get_return_url($order);
                $md5 = md5($this->id_seller . $result['title'] . $this->payment_data['amount'] . $this->crc . $this->security_code);
                unset ($_SESSION['tpay_session']);
                unset ($_SESSION['tpay_attempts']);
                update_post_meta($order->ID, '_transaction_id', $result['transactionId']);
                update_post_meta($order->ID, '_md5_checksum', $md5);
                update_post_meta($order->ID, '_crc', $this->crc);
                update_post_meta($order->ID, '_payment_method', $this->id);

                if ($user_id) {
                    update_post_meta($order->ID, '_customer_user', $user_id);
                }

                $this->gateway_helper->tpay_logger('Udane zamówienie, płatność kartą na stronie sklepu, redirect na: ' . $redirect);

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

    public function process_transaction($order)
    {
        try {
            $transaction = $this->tpay_api()->Transactions->createTransactionWithInstantRedirection($this->payment_data);
        } catch (\Error $e) {
            $this->gateway_helper->tpay_logger('Nieudana próba utworzenia transakcji kartą dla zamówienia ' . $order->ID);

            return false;
        }

        $md5 = md5($this->id_seller . $transaction['title'] . $this->payment_data['amount'] . $this->crc . $this->security_code);
        update_post_meta($order->ID, '_transaction_id', $transaction['transactionId']);
        update_post_meta($order->ID, '_md5_checksum', $md5);
        update_post_meta($order->ID, '_crc', $this->crc);
        $result = $this->tpay_api()->Transactions->createInstantPaymentByTransactionId($this->additional_payment_data, $transaction['transactionId']);
        update_option('CREATE_PAYMENT' . time(), print_r($result, true));

        if ($result['result'] == 'success') {
            return $result;
        } else {
            return [
                'result' => 'error',
                'message' => __('Unable to create transaction', 'tpay') . '<pre>' . print_r($result, true) . '</pre>',
            ];
        }
    }
}
