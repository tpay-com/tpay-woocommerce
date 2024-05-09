<?php

namespace Tpay\Helpers;

class RequestHelper
{
    public function get(?string $field = null, ?int $filter = null)
    {
        if (!$field) {
            $result = [];

            foreach ($_POST as $key => $val) {
                $result[$key] = filter_var($val, $filter ?: FILTER_DEFAULT);
            }

            return $result;
        }

        if (!isset($_POST[$field])) {
            return null;
        }

        return filter_var($_POST[$field], $filter ?: FILTER_DEFAULT);
    }
}
