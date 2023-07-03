<?php

namespace Tpay\Helpers;

class RequestHelper
{
    public function get($field = null){
        if(!isset($_POST[$field])){
            return null;
        }
        elseif(!$field){
            $result = [];
            foreach($_POST as $key => $val){
                $result[$key] = filter_input(INPUT_POST, $key);
            }
            return $result;
        }
        return filter_input(INPUT_POST, $field);
    }


}