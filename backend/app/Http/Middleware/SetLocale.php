<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const array VALID_LOCALES = ['id', 'en'];
    private const string DEFAULT_LOCALE = 'id';

    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->resolveLocale($request));

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $user = Auth::user();
        if ($user instanceof User) {
            $preference = $user->getAttribute('language_preference');
            if (is_string($preference) && in_array($preference, self::VALID_LOCALES, true)) {
                return $preference;
            }
        }

        $header = $request->header('Accept-Language');
        if (is_string($header) && $header !== '') {
            $segment = strtolower(explode('-', explode(',', $header)[0])[0]);
            if (in_array($segment, self::VALID_LOCALES, true)) {
                return $segment;
            }
        }

        return self::DEFAULT_LOCALE;
    }
}
