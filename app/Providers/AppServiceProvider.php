<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Desactivamos el forzado de esquema HTTPS según tu solicitud
        /*
        if (app()->environment("production")) { 
            URL::forceScheme("https"); 
        }
        */
    }
}
