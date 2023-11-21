<?php

namespace Tpay\Dtos;

class Group
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var Image */
    public $image;

    public function __construct(int $id, string $name, Image $image)
    {
        $this->id = $id;
        $this->name = $name;
        $this->image = $image;
    }
}
