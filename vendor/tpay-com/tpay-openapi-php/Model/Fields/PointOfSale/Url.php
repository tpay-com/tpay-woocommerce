<?php
namespace tpaySDK\Model\Fields\PointOfSale;

use tpaySDK\Model\Fields\Field;

class Url extends Field
{
    protected $name = __CLASS__;

    protected $type = self::STRING;

    protected $maxLength = 255;

    protected $pattern = '.*';

}
