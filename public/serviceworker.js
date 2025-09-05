const cacheName = "filament-pwa-cache-v1";
const urlsToCache = [
    "/",
    "/offline.html",
    "/admin",
    "/css/app.css",
    "/js/app.js",
];

self.addEventListener("install", (e) => {
    e.waitUntil(
        caches.open(cacheName).then((cache) => cache.addAll(urlsToCache))
    );
});

self.addEventListener("fetch", (e) => {
    if (e.request.mode === "navigate") {
        e.respondWith(
            fetch(e.request).catch(() => caches.match("/offline.html"))
        );
        return;
    }
    e.respondWith(
        caches.match(e.request).then((res) => res || fetch(e.request))
    );
});

// Background Sync (one-off)
self.addEventListener("sync", (event) => {
    if (event.tag === "sync-content") {
        event.waitUntil(
            // Placeholder: implement background sync logic, e.g., retry queued POSTs
            Promise.resolve()
        );
    }
});

// Periodic Background Sync
self.addEventListener("periodicsync", (event) => {
    if (event.tag === "periodic-sync-content") {
        event.waitUntil(
            // Placeholder: refresh cached content periodically
            caches.open(cacheName).then((cache) => cache.addAll(urlsToCache))
        );
    }
});

// Push notifications
self.addEventListener("push", (event) => {
    const data = event.data
        ? event.data.json()
        : { title: "Notification", body: "You have a new message." };
    event.waitUntil(
        self.registration.showNotification(data.title || "Notification", {
            body: data.body || "",
            icon: "/logo.png",
        })
    );
});
