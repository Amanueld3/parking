const cacheName = "filament-pwa-cache-v2";
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
    // Queue failed mutating requests for background sync
    if (["POST", "PUT", "PATCH", "DELETE"].includes(e.request.method)) {
        e.respondWith(
            fetch(e.request.clone()).catch(async () => {
                const cloned = e.request.clone();
                const body = await cloned.text();
                await queueRequest({
                    url: e.request.url,
                    method: e.request.method,
                    headers: Object.fromEntries([
                        ...e.request.headers.entries(),
                    ]),
                    body,
                    timestamp: Date.now(),
                });
                return new Response(JSON.stringify({ queued: true }), {
                    status: 202,
                    headers: { "Content-Type": "application/json" },
                });
            })
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
        event.waitUntil(flushQueue());
    }
});

// Periodic Background Sync
self.addEventListener("periodicsync", (event) => {
    if (event.tag === "periodic-sync-content") {
        event.waitUntil(
            caches.open(cacheName).then(async (cache) => {
                for (const url of urlsToCache) {
                    try {
                        await cache.add(new Request(url, { cache: "reload" }));
                    } catch (_) {}
                }
            })
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

// IndexedDB-based request queue for Background Sync
const DB_NAME = "bg-sync-db";
const STORE = "requests";

function openDb() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, 1);
        req.onupgradeneeded = () => {
            req.result.createObjectStore(STORE, {
                keyPath: "id",
                autoIncrement: true,
            });
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

async function queueRequest(item) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, "readwrite");
        tx.objectStore(STORE).add(item);
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

async function flushQueue() {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, "readwrite");
        const store = tx.objectStore(STORE);
        const cursorReq = store.openCursor();
        cursorReq.onsuccess = async (e) => {
            const cursor = e.target.result;
            if (!cursor) return;
            const r = cursor.value;
            try {
                await fetch(r.url, {
                    method: r.method,
                    headers: r.headers,
                    body: r.body,
                });
                store.delete(cursor.key);
            } catch (_) {}
            cursor.continue();
        };
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}
