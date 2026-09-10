<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menahan situs di halaman "sedang dalam pengembangan" sampai peluncuran.
 *
 * Penyaringnya host: hanya host di daftar izin
 * (config('carbon-calculator.coming_soon.allowed_hosts')) yang membuka situs
 * penuh — mis. subdomain uji coba dan localhost. Semua host lain ditahan.
 *
 * Daftar IZIN, bukan daftar blokir. Kalau logikanya dibalik, host yang belum
 * terpikirkan — subdomain cPanel, wildcard DNS, akses lewat alamat IP,
 * hostname bawaan server — akan menyajikan situs asli tanpa disadari.
 *
 * Maintenance mode bawaan Laravel sengaja tidak dipakai: `php artisan down`
 * mematikan SELURUH domain sekaligus dan butuh akses artisan di server —
 * padahal hosting kampanye ini tanpa SSH (lihat deploy/DEPLOY.md).
 *
 * Jawabannya 503, bukan 200, supaya mesin pencari memperlakukan situs sebagai
 * "belum siap, datang lagi nanti" dan tidak pernah mengindeks halaman
 * penahan ini sebagai isi situs.
 */
class ComingSoon
{
    /** Nama cookie penanda pratinjau dan umurnya (menit). */
    private const COOKIE = 'pratinjau';

    private const COOKIE_MINUTES = 480;

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('carbon-calculator.coming_soon.enabled')) {
            return $next($request);
        }

        $allowed = array_map('strtolower', (array) config('carbon-calculator.coming_soon.allowed_hosts', []));

        if (in_array(strtolower($request->getHost()), $allowed, true)) {
            return $next($request);
        }

        // Area admin tidak pernah ditahan: tim kampanye tetap perlu membuka
        // daftar peserta dan mengirim ulang email walau situs publik ditutup.
        if ($request->is('admin', 'admin/*')) {
            return $next($request);
        }

        $secret = config('carbon-calculator.coming_soon.secret');

        if ($secret) {
            // Pintu masuk: ?lihat=<secret>. Rahasianya ditukar cookie lalu
            // dibuang dari URL, supaya tidak ikut tersalin saat tautannya
            // dibagikan atau tercatat di log dan Analytics.
            if (hash_equals($secret, (string) $request->query('lihat'))) {
                return redirect($request->url())
                    ->withCookie(cookie(self::COOKIE, $this->token($secret), self::COOKIE_MINUTES));
            }

            if (hash_equals($this->token($secret), (string) $request->cookie(self::COOKIE))) {
                return $next($request);
            }
        }

        return response()
            ->view('pages.coming-soon', [], Response::HTTP_SERVICE_UNAVAILABLE)
            ->header('Retry-After', (string) (60 * 60 * 24))
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /** Yang disimpan di cookie adalah turunannya, bukan rahasianya sendiri. */
    private function token(string $secret): string
    {
        return hash_hmac('sha256', 'coming-soon', $secret);
    }
}
