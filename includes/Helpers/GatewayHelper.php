<?php

namespace Tpay\Helpers;

class GatewayHelper
{
    const CONDITION_PL = 'https://secure.tpay.com/regulamin.pdf';
    const CONDITION_EN = 'https://secure.tpay.com/regulamin.pdf';
    const PRIVACY_PL = 'https://tpay.com/user/assets/files_for_download/klauzula-informacyjna-platnik.pdf';
    const PRIVACY_EN = 'https://tpay.com/user/assets/files_for_download/klauzula-informacyjna-platnik.pdf';
    public $additional_payment_data;
    public $crc;
    public $card_helper;

    function __construct()
    {
        $this->card_helper = new CardHelper;
    }

    public function set_additional_payment_data($gateway_id, $payment_data, $crc, $post_data = null)
    {
        $result = [];
        $this->crc = $crc;
        $result = [
            'groupId' => $payment_data['pay']['groupId'],
            'method' => $payment_data['pay']['method'],
        ];

        switch ($gateway_id) {
            case TPAYBLIK_ID;
                if(get_option('woocommerce_tpayblik_settings')['enable_blik0'] == 'yes'){
                    $result = $result + $this->blik_payment_data($post_data);
                }
                break;

            case TPAYSF_ID;
                    $result = $result +  $this->sf_payment_data($post_data);
                break;

            default;
                $result = true;
                break;
        }
        return $result;
    }

    public function tpay_logger($log){
        $context = array( 'source' => 'tpay' );
        $logger = wc_get_logger();
        $logger->debug( $log . "\r\n-----------\r\n", $context );
    }

    private function sf_payment_data($post_data){
        if($card_id = $post_data['saved-card']){
            $card = CardHelper::get_card_by_id($card_id);
            if($card){
                $this->additional_payment_data['cardPaymentData'] = [
                    'token' => $card['token'],
                ];
            }
        }
        elseif($carddata = $post_data['carddata']){
            $this->additional_payment_data['cardPaymentData'] = [
                'card' => $carddata,
            ];
            if($post_data['save-card']){
                $save_card = [
                    'card_vendor' => $post_data['card_vendor'],
                    'card_hash' => $post_data['card_hash'],
                    'card_short_code' => $post_data['card_short_code'],
                    'crc' => $this->crc,
                ];
                if($this->card_helper->save_card($save_card)){
                    $this->additional_payment_data['cardPaymentData']['save'] = true;
                }
            }
        }
        return $this->additional_payment_data;
    }

    private function blik_payment_data($post_data){
        if($post_data['blik0']){
            $blik0 = str_replace('-', '', $post_data['blik0']);
            if($post_data['user_blik_alias']){
                $this->additional_payment_data['blikPaymentData'] = [
                    'blikToken' => $blik0,
                    'type' => 0,
                    'aliases' => [
                        'value' => $post_data['user_blik_alias'],
                        'type' => 'UID',
                        'label' => get_bloginfo('name')
                    ]
                ];
            }
            else{
                $this->additional_payment_data['blikPaymentData'] = [
                    'blikToken' => $blik0,
                    'type' => 0,
                ];
            }
        }
        else{
            if(!$post_data['user_has_saved_blik_alias']){
                wc_add_notice(__('Enter Blik code', 'tpay'), 'error');
                return false;
            }
            else{
                $this->additional_payment_data['blikPaymentData'] = [
                    'aliases' => [
                        'value' => $post_data['user_blik_alias'],
                        'type' => 'UID',
                        'label' => get_bloginfo('name')
                    ],
                    'type' => 0,
                ];
            }
        }
        return $this->additional_payment_data;
    }

    /**
     * @return string
     */
    public function get_condition_url()
    {
        return get_locale() === 'pl_PL' ? self::CONDITION_PL : self::CONDITION_EN;
    }

    /**
     * @return string
     */
    public function get_privacy_policy_url()
    {
        return get_locale() === 'pl_PL' ? self::PRIVACY_PL : self::PRIVACY_EN;
    }

    /**
     * @return string
     */
    public function agreements_field()
    {
        return sprintf('<div class="tpay-accept-conditions">
                    <p>By paying, <a href="%s" target="_blank">you accept the terms and conditions</a>.</p>
                    <p>The administrator of the personal data is Krajowy Integrator Płatności S.A., headquartered in Poznań.<br />
                    <a href="%s" target="_blank">Read the full text.</a>
                    </p>
                    </div>', $this->get_condition_url(), $this->get_privacy_policy_url());
    }

    /**
     * @return int
     */
    function get_order_by_transaction_md5($md5)
    {
        global $wpdb;
        $sql = $wpdb->prepare('select post_id from ' . $wpdb->postmeta . ' where meta_value = %s', $md5);
        $order_id = $wpdb->get_var($sql);
        return $order_id;
    }

    /**
     * @return int
     */

    function get_order_by_transaction_id($id)
    {
        global $wpdb;
        $sql = $wpdb->prepare('select post_id from ' . $wpdb->postmeta . ' where meta_value = %s and meta_key = "_transaction_id"', $id);
        $order_id = $wpdb->get_var($sql);
        return $order_id;
    }

    function get_order_by_transaction_crc($crc)
    {
        global $wpdb;
        $sql = $wpdb->prepare('select post_id from ' . $wpdb->postmeta . ' where meta_value = %s and meta_key = "_crc"', $crc);
        $order_id = $wpdb->get_var($sql);
        return $order_id;
    }

    function user_blik_status()
    {
        if (!get_current_user_id()) {
            $user_blik_alias = false;
            $user_has_saved_blik_alias = false;
        } else {
            $user_blik_alias = WP_TPAY_BLIK_PREFIX . '_' . get_current_user_id();
            $user_has_saved_blik_alias = get_user_meta(get_current_user_id(), 'tpay_alias_blik', true) ? true : false;
        }
        return [$user_blik_alias, $user_has_saved_blik_alias];
    }

    function payer_data($order)
    {
        return [
            'email' => $order->get_billing_email(),
            'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'phone' => $order->get_billing_phone(),
            'address' => $order->get_billing_address_1() . ', ' . $order->get_billing_address_2(),
            'code' => $order->get_billing_postcode(),
            'city' => $order->get_billing_city(),
            'country' => $order->get_billing_country()
        ];
    }

    function update_blik_alias(){
        if($_POST['event'] && strpos($_POST['msg_value']['value'], WP_TPAY_BLIK_PREFIX) !== false){
            $event = $_POST['event'];
            $uid = $this->user_id_by_blik_alias($_POST['msg_value']['value']);
            if($event == 'ALIAS_UNREGISTER'){
                delete_user_meta($uid, 'tpay_alias_blik');
                header("HTTP/1.1 200 OK");
                echo 'TRUE';
            }
            elseif($event == 'ALIAS_REGISTER'){
                update_user_meta($uid, 'tpay_alias_blik', $_POST['msg_value']['value']);
                header("HTTP/1.1 200 OK");
                echo 'TRUE';
            }
        }
    }

    function user_id_by_blik_alias($alias){
        global $wpdb;
        $sql = $wpdb->prepare('select user_id from ' . $wpdb->usermeta . ' where meta_value = %s and meta_key = "tpay_alias_blik"', $alias);
        $user_id = $wpdb->get_var($sql);
        return $user_id;
    }

    public function tpay_has_errors($response){
        if($errors = @$response['payments']['errors']){
            $errors_list = [];
            foreach($errors as $error){
                array_push($errors_list, $error['errorMessage']);
            }
            return $errors_list;
        }
        return false;
    }

    function blik_error($error_code)
    {
        $errors = [
            100 => __('Other error', 'tpay'),
            101 => __('Payment declined by user', 'tpay'),
            102 => __('BLIK system general error', 'tpay'),
            103 => __('User insufficient funds / user authorization error', 'tpay'),
            104 => __('BLIK system or user timeout', 'tpay'),
        ];
        if ($errors[$error_code]) {
            return $errors[$error_code];
        }
        return $errors[100];
    }

}