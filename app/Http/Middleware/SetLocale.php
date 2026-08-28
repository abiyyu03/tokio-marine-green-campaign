<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menerapkan bahasa pilihan pengunjung (dropdown "ID / EN" di header).
 * Pilihan disimpan di session supaya bertahan lintas halaman tanpa login.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (in_array($locale, config('carbon-calculator.locales'), true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
