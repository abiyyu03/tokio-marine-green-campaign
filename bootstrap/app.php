<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // Halaman masuk admin sengaja TIDAK bernama `login`: nama itu akan
        // mengaktifkan tombol "Login" milik pengunjung di site-header dan
        // menaruh URL admin di HTML setiap halaman publik. Dua baris ini
        // menggantikan fallback route('login') bawaan framework — tanpanya
        // tamu yang membuka /admin/peserta dapat 500, bukan redirect.
        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        // Tanpa ini, admin yang sudah masuk lalu membuka halaman login akan
        // dilempar ke route bernama `home` (beranda kampanye) oleh
        // RedirectIfAuthenticated, tanpa jalan kembali ke area admin.
        $middleware->redirectUsersTo(fn () => route('admin.participants'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
