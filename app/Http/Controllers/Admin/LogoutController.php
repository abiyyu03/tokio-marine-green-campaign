<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Keluar dari area admin.
 *
 * Dipanggil lewat POST + @csrf, bukan tautan biasa: logout lewat GET bisa
 * dipicu diam-diam dari halaman lain (mis. sebuah <img src>).
 *
 * Controller invokable, bukan closure di routes/web.php, supaya
 * `php artisan route:cache` tetap bisa dipakai saat deploy.
 */
class LogoutController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::logout();

        // invalidate() membuang isi sesi, regenerateToken() mengganti token
        // CSRF-nya. Keduanya perlu supaya sesi lama benar-benar tidak berlaku.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
