<?php

namespace Withinboredom\BuildingBlocks;

use Closure;
use DateTimeImmutable;
use JetBrains\PhpStorm\ArrayShape;
use Withinboredom\BuildingBlocks\Enums\CacheResponseTerms;
use Withinboredom\ResponseCode\HttpResponseCode;

final class ResponseCacheControl
{
    private array $terms = [];

    /**
     * @param Closure $getEtag A function that returns the requested etag from the request (etag header)
     * @param Closure $getLastModified A function that returns the modified-since header from the request
     */
    public function __construct(private Closure $getEtag, private Closure $getModifiedSince)
    {
    }

    #[ArrayShape([
        'Last-Modified' => "array",
        'Etag' => "string[]",
        'Cache-Control' => "string[]",
        'Expires' => "string[]"
    ])] public function getCacheHeaders(
        string $etag,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable $lastModified = new DateTimeImmutable()
    ): array {
        $now = time();
        $secs = $expiresAt->getTimestamp() - $now;
        $this->addTerm(CacheResponseTerms::MaxAge, $secs);
        return [
            'Last-Modified' => [$lastModified->format(DATE_RFC7231)],
            'Etag' => [$etag],
            'Cache-Control' => [$this->getTerms()],
            'Expires' => [$expiresAt->format(DATE_RFC7231)],
        ];
    }

    public function addTerm(CacheResponseTerms $term, string|int|null $value = null): self
    {
        $this->terms[$term->value] = $value;
        return $this;
    }

    private function getTerms(): string
    {
        return implode(
            ', ',
            array_map(static fn($key, $value) => $value === null ? $key : "$key=$value",
                array_keys($this->terms),
                $this->terms)
        );
    }

    public function recommendStatus(string $etag, DateTimeImmutable $lastModified): HttpResponseCode|null
    {
        if (($this->getEtag)() === $etag || ($this->getModifiedSince)()->getTimestamp() >= $lastModified->getTimestamp(
            )) {
            return HttpResponseCode::NotModified;
        }

        return null;
    }
}

$cacheHeaders = new ResponseCacheControl(
    static fn() => $_SERVER['HTTP_IF_NONE_MATCH'] ?? '',
    static fn() => new DateTimeImmutable($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? 'this minute')
);
$headers = $cacheHeaders->addTerm(CacheResponseTerms::Immutable)->recommendStatus($etag, $expiresAt);
if (($status = $cacheHeaders->recommendStatus($etag, $expiresAt)) !== null) {
    http_response_code($status->value);
    die();
}