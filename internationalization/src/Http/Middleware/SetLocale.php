<?php

namespace IJIDeals\Internationalization\Http\Middleware;

use Closure;
use IJIDeals\UserManagement\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = null;
        $supportedLocales = array_keys(config('internationalization.supported_languages', ['en' => []]));
        $detectionOrder = config('internationalization.locale_detection_order', ['user_preference', 'browser']);

        foreach ($detectionOrder as $method) {
            switch ($method) {
                case 'url':
                    // Assumes the locale is the first segment of the path if route_localization.prefix is true
                    // e.g., your-domain.com/{locale}/other-segments
                    // This requires routes to be defined with a {locale} prefix.
                    if (config('internationalization.route_localization.enabled') && config('internationalization.route_localization.prefix')) {
                        $segment = $request->segment(1);
                        if ($segment && in_array($segment, $supportedLocales)) {
                            $locale = $segment;
                        }
                    }
                    break;

                case 'session':
                    if ($request->session()->has('locale') && in_array($request->session()->get('locale'), $supportedLocales)) {
                        $locale = $request->session()->get('locale');
                    }
                    break;

                case 'user_preference':
                    /** @var User $user */
                    $user = Auth::user();
                    // Ensure 'preferred_language' field exists on User model
                    if ($user && property_exists($user, 'preferred_language') && $user->preferred_language && in_array($user->preferred_language, $supportedLocales)) {
                        $locale = $user->preferred_language;
                    }
                    break;

                case 'browser':
                    $browserLocale = $request->getPreferredLanguage($supportedLocales);
                    if ($browserLocale) {
                        $locale = $browserLocale;
                    }
                    break;
            }

            if ($locale) {
                break; // Locale found, stop detection
            }
        }

        // Fallback to default application locale if no preferred locale was detected or supported
        if (! $locale || ! in_array($locale, $supportedLocales)) {
            $locale = config('internationalization.default_language', config('app.fallback_locale', 'en'));
        }

        // Ensure the final locale is supported, otherwise fallback to app's default/fallback
        if (! in_array($locale, $supportedLocales)) {
            $locale = config('app.fallback_locale', 'en');
            if (! in_array($locale, $supportedLocales) && ! empty($supportedLocales)) { // Final fallback if app.fallback_locale is not in supported list
                $locale = $supportedLocales[0];
            }
        }

        App::setLocale($locale);

        // Optionally, set the locale in the session for subsequent requests if detected from URL or browser
        if (in_array('session', $detectionOrder) && $request->session()->get('locale') !== $locale) {
            $request->session()->put('locale', $locale);
        }

        // It's also common to set the Carbon locale if Carbon is used for date formatting
        // Carbon::setLocale(App::getLocale()); // Or a mapping if Carbon locale names differ

        return $next($request);
    }
}
