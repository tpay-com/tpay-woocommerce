<?php

namespace Tpay\Dtos;

class Channel
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $fullName;

    /** @var Image */
    public $image;

    /** @var bool */
    public $available;

    /** @var bool */
    public $onlinePayment;

    /** @var bool */
    public $instantRedirection;

    /** @var array<Group> */
    public $groups;

    /** @var array<Constraint> */
    public $constraints;

    public function __construct(
        int $id,
        string $name,
        string $fullName,
        Image $image,
        bool $available,
        bool $onlinePayment,
        bool $instantRedirection,
        array $groups,
        array $constraints
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->fullName = $fullName;
        $this->image = $image;
        $this->available = $available;
        $this->onlinePayment = $onlinePayment;
        $this->instantRedirection = $instantRedirection;
        $this->groups = $groups;
        $this->constraints = $constraints;
    }
}
