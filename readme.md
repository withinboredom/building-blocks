# Random building blocks

## Turning errors into exceptions

The `ErrorControl` class allows for simply turning errors into exceptions:

```php
<?php

new class extends ErrorControl {
    protected function handleUnhandledException(Throwable $exception): void
    {
        $message = [
            'exception_type' => get_class($exception),
            'post' => file_get_contents('php://input'),
            'url' => $_SERVER['REQUEST_URI'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'message' => $exception->getMessage(),
            'line' => $exception->getLine(),
            'file' => $exception->getFile(),
            'trace' => $exception->getTraceAsString(),
            'num' => $exception->getCode(),
        ];
        error_log(json_encode($message, JSON_PRETTY_PRINT), 4);
        if (!headers_sent()) {
            http_response_code(HttpResponseCode::InternalServerError);
            die();
        }
    }

    protected function getIgnoredErrorLevel(): int
    {
        return E_DEPRECATED;
    }
};
```

## Cache-Control

The cache is a very important bit of information for a browser, which can save you a huge amount of bandwidth.

```php
$cacheHeaders = new ResponseCacheControl(
    static fn() => $_SERVER['HTTP_IF_NONE_MATCH'] ?? '',
    static fn() => new DateTimeImmutable($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? 'this minute')
);
if(($status = $cacheHeaders->recommendStatus($etag, $expiresAt)) !== null) {
    http_response_code($status->value);
    die();
}
$headers = $cacheHeaders->addTerm(CacheResponseTerms::Immutable)->recommendStatus($etag, $expiresAt);
// pass headers to PSR response object
```