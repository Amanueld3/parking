<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Filament::serving(function () {
            Filament::registerRenderHook(
                'panels::head.start',
                fn() => '<link rel="manifest" href="' . asset('manifest.json') . '">
                          <meta name="theme-color" content="#4CAF50">'
            );
        });
    }
}
