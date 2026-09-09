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
