<?php

use App\Http\Controllers\Admin\ExportSubmissionsController;
use App\Http\Controllers\Admin\ExportSubmissionsXlsxController;
use App\Http\Controllers\Admin\LogoutController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');
Route::livewire('/kalkulator', 'pages::calculator')->name('calculator');
Route::livewire('/kalkulator/hasil/{uuid}', 'pages::result')->name('calculator.result');
Route::livewire('/kalkulator/hasil/{uuid}/laporan', 'pages::report')->name('calculator.report');

/**
 * robots.txt dan sitemap.xml dilayani lewat route, bukan file statis di
 * public/, supaya alamat di dalamnya mengikuti APP_URL — tidak ada baris yang
 * perlu disunting manual setiap ganti domain. Server tetap melayani file
 * statis lebih dulu bila suatu saat file dengan nama sama ditaruh di public/.
 */
Route::get('/robots.txt', function () {
    $lines = [
        '# Tokio Marine Jaga Bumi - Kalkulator Karbon',
        '',
        'User-agent: *',
        'Allow: /',
        '',
        '# Area internal tim kampanye.',
        'Disallow: /admin/',
        '',
        '# Halaman hasil & laporan beralamat uuid dan berisi data peserta.',
        '# Halamannya sendiri juga mengirim <meta name="robots" content="noindex">.',
        'Disallow: /kalkulator/hasil/',
        '',
        'Sitemap: '.route('sitemap'),
        '',
    ];

    return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');

Route::get('/sitemap.xml', function () {
    // Hanya halaman publik. Hasil dan laporan sengaja tidak masuk: alamatnya
    // milik satu peserta dan isinya data pribadi.
    $pages = [
        ['url' => route('home'), 'view' => 'pages/home.blade.php', 'priority' => '1.0'],
        ['url' => route('calculator'), 'view' => 'pages/calculator.blade.php', 'priority' => '0.9'],
    ];

    $urls = collect($pages)->map(function (array $page) {
        // lastmod dari waktu ubah file view: jujur mengikuti isi halaman,
        // tidak berubah setiap hari seperti kalau memakai tanggal hari ini.
        $path = resource_path('views/'.$page['view']);
        $lastmod = date('Y-m-d', is_file($path) ? filemtime($path) : time());

        return '  <url>'
            .'<loc>'.e($page['url']).'</loc>'
            .'<lastmod>'.$lastmod.'</lastmod>'
            .'<changefreq>monthly</changefreq>'
            .'<priority>'.$page['priority'].'</priority>'
            .'</url>';
    })->implode("\n");

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
        .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
        .$urls."\n"
        .'</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
})->name('sitemap');

/**
 * Pemilih bahasa di header. Menyimpan pilihan ke session lalu kembali ke
 * halaman asal, sehingga posisi pengisian wizard tidak hilang.
 */
Route::get('/locale', function (Request $request) {
    $locale = $request->query('locale');

    if (in_array($locale, config('carbon-calculator.locales'), true)) {
        $request->session()->put('locale', $locale);
    }

    return back();
})->name('locale.switch');

/**
 * Area admin — internal tim kampanye.
 *
 * Tidak ada satu pun tautan ke sini dari halaman publik: tombol "Login" di
 * site-header tetap milik pengunjung, itu sebabnya rute masuk di bawah
 * bernama `admin.login`, bukan `login`. Redirect untuk tamu diatur di
 * bootstrap/app.php.
 */
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::livewire('/masuk', 'pages::admin.login')->name('login');
    });

    Route::middleware('auth')->group(function () {
        Route::redirect('/', '/admin/peserta')->name('index');

        // POST supaya terlindungi CSRF; link GET bisa dipicu dari situs lain.
        Route::post('/keluar', LogoutController::class)->name('logout');

        Route::livewire('/peserta', 'pages::admin.participants')->name('participants');

        // Didaftarkan SEBELUM /peserta/{uuid} dan uuid-nya dibatasi pola uuid:
        // dua lapis penjaga supaya "ekspor" tidak pernah tertangkap sebagai uuid.
        Route::get('/peserta/ekspor', ExportSubmissionsController::class)
            ->name('participants.export');

        Route::get('/peserta/ekspor-excel', ExportSubmissionsXlsxController::class)
            ->name('participants.export.xlsx');

        Route::livewire('/peserta/{uuid}', 'pages::admin.participant')
            ->name('participants.show')
            ->whereUuid('uuid');
    });
});
