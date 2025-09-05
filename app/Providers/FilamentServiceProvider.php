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
                function () {
                    $manifest = '<link rel="manifest" href="' . asset('manifest.json') . '">';
                    $theme = '<meta name="theme-color" content="#4CAF50">';
                    $sw = '<script>if ("serviceWorker" in navigator) { window.addEventListener("load", () => { navigator.serviceWorker.register("' . asset('serviceworker.js') . '").then(async (reg) => { if ("sync" in reg) { try { await reg.sync.register("sync-content"); } catch (e) {} } if ("periodicSync" in reg) { try { await reg.periodicSync.register("periodic-sync-content", { minInterval: 86400000 }); } catch (e) {} } }).catch(() => {}); }); }</script>';
                    return $manifest . "\n" . $theme . "\n" . $sw;
                }
            );
        });
    }
}
