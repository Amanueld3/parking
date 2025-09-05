const cacheName = "filament-pwa-cache-v1";
const urlsToCache = [
    "/",
    "/admin",
    "/css/app.css",
    "/js/app.js",
    "/vendor/filament/**/*.css",
    "/vendor/filament/**/*.js",
];

self.addEventListener("install", (e) => {
    e.waitUntil(
        caches.open(cacheName).then((cache) => cache.addAll(urlsToCache))
    );
});

self.addEventListener("fetch", (e) => {
    e.respondWith(
        caches.match(e.request).then((response) => response || fetch(e.request))
    );
});
