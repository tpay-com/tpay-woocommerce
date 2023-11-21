<?php

namespace Tpay\Dtos;

class Image
{
    /** @var string */
    public $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }
}
