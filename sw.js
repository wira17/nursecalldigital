
const VERSION = 'nursecall-v8';
const STATIC = ['offline.html', 'assets/icons/icon-192.png', 'assets/icons/icon-512.png'];

self.addEventListener('install', (ev) => {
  ev.waitUntil(caches.open(VERSION).then((c) => c.addAll(STATIC)).then(() => self.skipWaiting()));
});
self.addEventListener('activate', (ev) => {
  ev.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', (ev) => {
  const req = ev.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== location.origin) return;
  if (req.mode === 'navigate') {
    ev.respondWith(fetch(req).catch(() => caches.match('offline.html')));
    return;
  }
  // CSS & JS: selalu ambil versi terbaru dari server (cache hanya cadangan saat offline) agar update file langsung terpakai
  if (/\.(css|js)$/.test(url.pathname) && !url.pathname.endsWith('/sw.js')) {
    ev.respondWith(fetch(req).then((res) => {
      if (res.ok) { const copy = res.clone(); caches.open(VERSION).then((c) => c.put(req, copy)); }
      return res;
    }).catch(() => caches.match(req)));
    return;
  }
  // Gambar & font: cache dulu (jarang berubah)
  if (/\.(png|jpg|jpeg|svg|webp|woff2?)$/.test(url.pathname)) {
    ev.respondWith(caches.match(req).then((hit) => hit || fetch(req).then((res) => {
      if (res.ok) { const copy = res.clone(); caches.open(VERSION).then((c) => c.put(req, copy)); }
      return res;
    })));
  }
});
self.addEventListener('notificationclick', (ev) => {
  ev.notification.close();
  const target = (ev.notification.data && ev.notification.data.url) || self.registration.scope;
  ev.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
    for (const c of list) { if (c.url.startsWith(self.registration.scope) && 'focus' in c) { c.navigate(target); return c.focus(); } }
    return self.clients.openWindow(target);
  }));
});