<?php

namespace App\Services;

class SiteUrl
{
    public static function origin(): string
    {
        // Optional canonical domain for installations with several aliases or a separate API.
        if ($configured = config('app.site_url')) {
            return rtrim($configured, '/');
        }
        if (app()->bound('request') && request()->getHost()) {
            return request()->getSchemeAndHttpHost();
        }

        return rtrim(config('app.url'), '/');
    }
}
