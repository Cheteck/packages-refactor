<?php

namespace IJIDeals\Internationalization\Helpers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

class RouteLocalizationHelper
{
    /**
     * Generate a localized route URL.
     */
    public static function trans(string $name, array $parameters = [], ?string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        $routeName = $locale.'.'.$name;
        if (Route::has($routeName)) {
            return route($routeName, $parameters);
        }
        // Fallback to default language if not found
        $defaultLocale = config('internationalization.default_language', 'en');
        $fallbackRouteName = $defaultLocale.'.'.$name;
        if (Route::has($fallbackRouteName)) {
            return route($fallbackRouteName, $parameters);
        }

        // Fallback to non-localized route
        return route($name, $parameters);
    }
}
