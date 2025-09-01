<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $this->getLocale($request);
        
        if ($this->isValidLocale($locale)) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        }

        return $next($request);
    }

    /**
     * Determine the locale for the request
     */
    private function getLocale(Request $request): string
    {
        // 1. Check for explicit locale in query parameter
        if ($request->has('locale') && $this->isValidLocale($request->input('locale'))) {
            return $request->input('locale');
        }

        // 2. Check for locale in Accept-Language header
        if ($request->header('Accept-Language')) {
            $headerLocale = $this->parseAcceptLanguageHeader($request->header('Accept-Language'));
            if ($this->isValidLocale($headerLocale)) {
                return $headerLocale;
            }
        }

        // 3. Check for stored locale in session
        if (Session::has('locale') && $this->isValidLocale(Session::get('locale'))) {
            return Session::get('locale');
        }

        // 4. Fall back to application default
        return config('app.locale', 'en');
    }

    /**
     * Parse Accept-Language header to get the preferred locale
     */
    private function parseAcceptLanguageHeader(string $acceptLanguage): ?string
    {
        $locales = [];
        
        foreach (explode(',', $acceptLanguage) as $item) {
            $parts = explode(';', trim($item));
            $locale = trim($parts[0]);
            $quality = isset($parts[1]) ? (float) str_replace('q=', '', trim($parts[1])) : 1.0;
            
            // Extract just the language part (e.g., 'en' from 'en-US')
            $language = explode('-', $locale)[0];
            
            $locales[$language] = $quality;
        }

        // Sort by quality (highest first)
        arsort($locales);

        // Return the highest quality locale that we support
        foreach (array_keys($locales) as $locale) {
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        return null;
    }

    /**
     * Check if the given locale is supported
     */
    private function isValidLocale(string $locale): bool
    {
        return in_array($locale, config('app.supported_locales', ['en']));
    }
}