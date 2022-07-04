<?php

namespace Withinboredom\BuildingBlocks;

use Withinboredom\ResponseCode\HttpResponseCode;

class Result
{
    public function __construct(public readonly HttpResponseCode $code, public readonly string|null $body = null)
    {
    }

    public function emit(): never
    {
        http_response_code($this->code->value);
        if ($this->body !== null) {
            echo $this->body;
        }
        die();
    }
}