<?php

namespace Tpay\Helpers;

class CardHelper
{
    public function save_card($card)
    {
        global $wpdb;
        $hash = sha1($card['card_hash'].WP_TPAY_HASH);
        $table = $wpdb->prefix.'tpay_cards';
        $wpdb->delete($table, ['hash' => $hash, 'source_order' => null]);
        $sql = $wpdb->prepare('select id from '.$table.' where `hash` = %s', $hash);
        $card_id = $wpdb->get_var($sql);
        if (!$card_id) {
            $card_id = $wpdb->insert($table, [
                'vendor' => $card['card_vendor'],
                'hash' => $hash,
                'short_code' => $card['card_short_code'],
                'crc' => $card['crc'],
                'user_id' => get_current_user_id(),
            ]);

            return (bool) $card_id;
        }

        return false;
    }

    public static function get_card_by_id($card_id)
    {
        if (get_current_user_id()) {
            global $wpdb;
            $table = $wpdb->prefix.'tpay_cards';
            $sql = $wpdb->prepare('select * from '.$table.' where `id` = %d and `user_id` = %d and `token` is not null', $card_id, get_current_user_id());
            $card = $wpdb->get_row($sql, ARRAY_A);

            return $card;
        }

        return false;
    }

    public function update_card_token($user_id, $crc, $token, $order_id)
    {
        global $wpdb;
        $sql = $wpdb->prepare('select id from '.$wpdb->prefix.'tpay_cards where crc = %s and user_id = %d', $crc, $user_id);
        $card_id = $wpdb->get_var($sql);
        if ($card_id) {
            $wpdb->update(
                $wpdb->prefix.'tpay_cards',
                ['token' => $token, 'source_order' => $order_id],
                ['id' => $card_id]
            );
        }
    }
}
