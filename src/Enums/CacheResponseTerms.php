<?php

namespace Withinboredom\BuildingBlocks\Enums;

enum CacheResponseTerms: string
{
    case Public = 'public';
    case Private = 'private';
    case NoCache = 'no-cache';
    case NoStore = 'no-store';
    case MaxAge = 'max-age';
    case NoTransform = 'no-transform';
    case MustRevalidate = 'must-revalidate';
    case ProxyRevalidate = 'proxy-revalidate';
    case SMaxAge = 's-maxage';
    case MustUnderstand = 'must-understand';
    case Immutable = 'immutable';
    case StaleWhileRevalidate = 'stale-while-revalidate';
    case StaleIfError = 'stale-if-error';
}