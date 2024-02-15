<?php

namespace Tpay\Helpers;

class DatabaseConnection
{
    public static function delete(string $table, array $where, ?array $whereFormat = null)
    {
        global $wpdb;

        $wpdb->delete(self::applyPrefix($table), $where, $whereFormat);
    }

    /**
     * Query should be in format acceptable by sprintf function
     *
     * @return array|object|null
     */
    public static function query(string $query, ...$args)
    {
        global $wpdb;

        $args[0] = self::applyPrefix($args[0]);

        return $wpdb->get_results($wpdb->prepare($query, ...$args), ARRAY_A);
    }

    /**
     * @return array|object|\stdClass|null
     */
    public static function queryOne(string $query, ...$args)
    {
        global $wpdb;

        $args[0] = self::applyPrefix($args[0]);

        return $wpdb->get_row($wpdb->prepare($query, ...$args), ARRAY_A);
    }

    public static function queryVar(string $query, ...$args): ?string
    {
        global $wpdb;

        $args[0] = self::applyPrefix($args[0]);

        return $wpdb->get_var($wpdb->prepare($query, ...$args));
    }

    /**
     * @return bool|int|\mysqli_result|null
     */
    public static function insert(string $table, array $data)
    {
        global $wpdb;

        return $wpdb->insert(self::applyPrefix($table), $data);
    }

    public static function update(string $table, array $data, array $where)
    {
        global $wpdb;

        return $wpdb->update(self::applyPrefix($table), $data, $where);
    }

    private static function applyPrefix(string $table): string
    {
        global $wpdb;

        return sprintf('%s%s', $wpdb->prefix, $table);
    }
}
