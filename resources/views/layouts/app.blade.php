<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ asset('serviceworker.js') }}').then(async (reg) => {
                    // Background sync
                    if ('sync' in reg) {
                        try {
                            await reg.sync.register('sync-content');
                        } catch (e) {}
                    }
                    // Periodic background sync
                    if ('periodicSync' in reg) {
                        try {
                            await reg.periodicSync.register('periodic-sync-content', {
                                minInterval: 24 * 60 * 60 * 1000
                            });
                        } catch (e) {}
                    }

                    // Web Push subscription
                    try {
                        const vapidKey = @json(config('services.webpush.vapid_public_key'));
                        if (vapidKey) {
                            const sub = await reg.pushManager.getSubscription() || await reg.pushManager
                                .subscribe({
                                    userVisibleOnly: true,
                                    applicationServerKey: urlBase64ToUint8Array(vapidKey),
                                });
                            await fetch('/push/subscribe', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify(sub),
                            });
                        }
                    } catch (_) {}
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
    <meta name="theme-color" content="#4CAF50">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-152x152.png') }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
    @PwaHead


</head>

<body class="font-sans antialiased">
    @RegisterServiceWorkerScript

    <x-banner />

    <div class="min-h-screen bg-gray-100">
        @livewire('navigation-menu')

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="px-4 py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    @stack('modals')

    @livewireScripts
</body>

</html>
