<?php

namespace Tpay\Helpers;

class RequestHelper
{
    public function get(?string $field = null, ?int $filter = null)
    {
        if (!isset($_POST[$field])) {
            return null;
        }
        if (!$field) {
            $result = [];

            foreach ($_POST as $key => $val) {
                $result[$key] = filter_input(INPUT_POST, $key, $filter ?: FILTER_DEFAULT);
            }

            return $result;
        }

        return filter_input(INPUT_POST, $field, $filter ?: FILTER_DEFAULT);
    }
}
