<?php

namespace Withinboredom\BuildingBlocks;

use ErrorException;
use Throwable;
use Withinboredom\ResponseCode\HttpResponseCode;

use function Withinboredom\ResponseCode\http_response_code;

abstract class ErrorControl
{
    public function __construct(protected readonly int $errorLevel = E_ALL)
    {
        set_error_handler($this->transformErrorToException(...), $errorLevel);
        set_exception_handler($this->handleUnhandledException(...));
        register_shutdown_function($this->handleShutdown(...));
    }

    /**
     * Transforms an error to an exception and calls the unhandled exception handler.
     */
    protected function transformErrorToException($errno, $errstr, $errfile, $errline): void
    {
        if ($errno & $this->getIgnoredErrorLevel()) {
            return;
        }

        $this->handleUnhandledException(new ErrorException($errstr, 0, $errno, $errfile, $errline));
    }

    /**
     * Return the error level that should be ignored.
     *
     * Ex: E_DEPRECATED
     *
     * @return int
     */
    abstract protected function getIgnoredErrorLevel(): int;

    /**
     * MUST handle an exception and either rethrow it or die.
     *
     * @param Throwable $exception
     */
    abstract protected function handleUnhandledException(Throwable $exception): void;

    /**
     * Called just before shutdown.
     */
    protected function handleShutdown(): void
    {
        $lastError = error_get_last();
        if ($lastError === null) {
            return;
        }

        if (!headers_sent()) {
            http_response_code(HttpResponseCode::InternalServerError);
        }

        if ($lastError['type'] === E_ERROR || $lastError['type'] === E_PARSE) {
            $this->handleUnhandledException(
                new ErrorException($lastError['message'], 0, 0, $lastError['file'], $lastError['line'])
            );
        }
    }
}
