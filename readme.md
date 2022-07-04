# Random building blocks

This is a library that totally ignores PSRs with a focus on simplicity. It is not a library for building complex
applications, but rather when you just need to get stuff done with minimal fuss.

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

## A ridiculously simple router

A really simple router working in O(routes + parameters) time.

```php
$router = new Router($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
$router->addRoute('/', static fn() => new Result(\Withinboredom\ResponseCode\HttpResponseCode::Ok, 'Hello, world!'));
$router->addRoute('/hello/:name', static fn(string $name) => new Result(\Withinboredom\ResponseCode\HttpResponseCode::Ok, "Hello, $name!"));

// emit the result of the routing and die
$router->doRouting()->emit();
```