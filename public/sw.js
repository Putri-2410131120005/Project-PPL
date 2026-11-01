const CACHE_NAME = 'smart-abk-shell-v1';
const baseUrl = new URL(self.registration.scope);
const assetPath = (path) => new URL(path, baseUrl).pathname;
const offlineUrl = assetPath('offline');
const manifestUrl = assetPath('manifest.webmanifest');
const icon192Url = assetPath('icons/icon-192.png');
const icon512Url = assetPath('icons/icon-512.png');
const APP_SHELL = [offlineUrl, manifestUrl, icon192Url, icon512Url];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))));
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(offlineUrl)));
        return;
    }

    const isSafeStaticAsset = url.pathname.startsWith(assetPath('build/'))
        || url.pathname === manifestUrl
        || url.pathname.startsWith(assetPath('icons/'));
    if (! isSafeStaticAsset) return;

    event.respondWith(caches.match(request).then((cached) => cached || fetch(request).then((response) => {
        if (response.ok) caches.open(CACHE_NAME).then((cache) => cache.put(request, response.clone()));
        return response;
    })));
});

self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    event.waitUntil(self.registration.showNotification(data.title || 'SMART-ABK', {
        body: data.body || 'Ada pembaruan baru.',
        icon: icon192Url,
        badge: icon192Url,
        tag: data.tag || 'smart-abk-notification',
        data: { url: data.url || baseUrl.pathname },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const destination = new URL(event.notification.data?.url || baseUrl.pathname, baseUrl).href;
    event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientsList) => {
        const existing = clientsList.find((client) => client.url === destination);
        return existing ? existing.focus() : clients.openWindow(destination);
    }));
});
