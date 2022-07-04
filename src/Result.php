<?php

namespace Withinboredom\BuildingBlocks;

use Throwable;
use Withinboredom\ResponseCode\HttpResponseCode;

class Result
{
    private static int $EXCEPTION_SERIALIZATION_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES;

    public function __construct(
        public readonly HttpResponseCode $code,
        public readonly string|null|Throwable $body = null
    ) {
    }

    public static function setExceptionSerializationFlags(int $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
    ): void {
        self::$EXCEPTION_SERIALIZATION_FLAGS = $flags;
    }

    public function emit(): never
    {
        http_response_code($this->code->value);
        if ($this->body !== null) {
            echo $this->body instanceof Throwable ? json_encode(['errors' => [$this->body->getMessage()]],
                self::$EXCEPTION_SERIALIZATION_FLAGS) : $this->body;
        }
        die();
    }
}