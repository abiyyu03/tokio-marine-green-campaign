<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // SSL terpasang di hosting, tapi APP_URL bisa saja terbaca sebagai
        // http:// sehingga asset() menghasilkan URL campuran yang diblokir
        // browser. Dipaksa https hanya di produksi agar `php artisan serve`
        // dan Vite dev server di lokal tetap jalan lewat http.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
