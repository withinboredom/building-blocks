<?php

namespace Withinboredom\BuildingBlocks\Enums;

enum CacheRequestTerms: string
{
    case MaxAge = 'max-age';
    case MaxStale = 'max-stale';
    case MinFresh = 'min-fresh';
    case NoCache = 'no-cache';
    case NoStore = 'no-store';
    case NoTransform = 'no-transform';
    case OnlyIfCached = 'only-if-cached';
    case StaleIfError = 'stale-if-error';
}