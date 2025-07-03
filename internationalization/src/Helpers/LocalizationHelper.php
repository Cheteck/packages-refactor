<?php

namespace IJIDeals\Internationalization\Helpers;

use Illuminate\Support\Facades\App;

class LocalizationHelper
{
    /**
     * Format a date according to the user's locale.
     */
    public static function formatDate($date, $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        $format = config("internationalization.localization.date_format.$locale", 'Y-m-d');

        return $date ? $date->format($format) : '';
    }

    /**
     * Format a time according to the user's locale.
     */
    public static function formatTime($date, $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        $format = config("internationalization.localization.time_format.$locale", 'H:i');

        return $date ? $date->format($format) : '';
    }

    /**
     * Format a datetime according to the user's locale.
     */
    public static function formatDateTime($date, $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        $format = config("internationalization.localization.datetime_format.$locale", 'Y-m-d H:i:s');

        return $date ? $date->format($format) : '';
    }

    /**
     * Format a number according to the user's locale.
     */
    public static function formatNumber($number, $locale = null, $decimals = 2): string
    {
        $locale = $locale ?? App::getLocale();
        $decimal = config("internationalization.localization.number_format.decimal_separator.$locale", '.');
        $thousands = config("internationalization.localization.number_format.thousands_separator.$locale", ',');

        return number_format($number, $decimals, $decimal, $thousands);
    }

    /**
     * Format a currency value according to the user's locale.
     */
    public static function formatCurrency($amount, $currency = 'USD', $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        $formatted = self::formatNumber($amount, $locale, 2);

        // You can extend this to use intl/NumberFormatter for more advanced formatting
        return "$formatted $currency";
    }
}
