<?php

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
