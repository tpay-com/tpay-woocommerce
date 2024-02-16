<?php

namespace Tpay\Helpers;

class CardHelper
{
    protected const TABLE = 'tpay_cards';

    public function save_card(array $card): bool
    {
        $hash = sha1($card['card_hash'].WP_TPAY_HASH);
        DatabaseConnection::delete(self::TABLE, ['hash' => $hash, 'source_order' => null]);
        $card_id = DatabaseConnection::queryVar('SELECT id FROM %i WHERE hash = %s', self::TABLE, $hash);

        if (!$card_id) {
            $result = DatabaseConnection::insert(self::TABLE, [
                'vendor' => $card['card_vendor'],
                'hash' => $hash,
                'short_code' => $card['card_short_code'],
                'crc' => $card['crc'],
                'user_id' => get_current_user_id(),
            ]);

            return (bool) $result;
        }

        return false;
    }

    public static function get_card_by_id($card_id)
    {
        if (get_current_user_id()) {
            return DatabaseConnection::queryOne(
                'SELECT * FROM %i WHERE id = %d AND user_id = %d AND token IS NOT NULL',
                self::TABLE,
                $card_id,
                get_current_user_id()
            );
        }

        return false;
    }

    public function update_card_token($user_id, $crc, $token, $order_id)
    {
        $card_id = DatabaseConnection::queryVar('SELECT id FROM %i WHERE crc = %s AND user_id = %s', self::TABLE, $crc, $user_id);

        if ($card_id) {
            DatabaseConnection::update(
                self::TABLE,
                ['token' => $token, 'source_order' => $order_id],
                ['id' => $card_id]
            );
        }
    }
}
