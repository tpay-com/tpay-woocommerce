<?php

namespace Tpay\Dtos;

class Constraint
{
    /** @var string */
    public $field;

    /** @var string */
    public $type;

    /** @var string */
    public $value;

    public function __construct(string $field, string $type, string $value)
    {
        $this->field = $field;
        $this->type = $type;
        $this->value = $value;
    }
}
