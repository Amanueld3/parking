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
            Filament::registerRenderHook('panels::head.start', function () {
                $manifestUrl = asset('manifest.json');
                $swUrl = asset('serviceworker.js');
                $vapid = json_encode(config('services.webpush.vapid_public_key'));
                $manifest = "<link rel=\"manifest\" href=\"{$manifestUrl}\">";
                $theme = '<meta name="theme-color" content="#4CAF50">';
                $sw = <<<HTML
<script>
if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
        navigator.serviceWorker.register("{$swUrl}").then(async (reg) => {
            if ("sync" in reg) { try { await reg.sync.register("sync-content"); } catch (e) {} }
            if ("periodicSync" in reg) { try { await reg.periodicSync.register("periodic-sync-content", { minInterval: 86400000 }); } catch (e) {} }
            try {
                const vapidKey = {$vapid};
                if (vapidKey) {
                    const sub = await reg.pushManager.getSubscription() || await reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(vapidKey),
                    });
                    await fetch('/push/subscribe', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(sub),
                    });
                }
            } catch (e) {}
        }).catch(() => {});
    });
}
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
    return outputArray;
}
</script>
HTML;
                return $manifest . "\n" . $theme . "\n" . $sw;
            });
        });
    }
}
