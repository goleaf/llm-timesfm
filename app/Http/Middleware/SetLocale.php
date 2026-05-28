<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys((array) config('localization.supported', ['ru' => 'RU', 'en' => 'EN']));
        $locale = session('app.locale', (string) config('localization.default', 'ru'));

        if (! in_array($locale, $supported, true)) {
            $locale = 'ru';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
