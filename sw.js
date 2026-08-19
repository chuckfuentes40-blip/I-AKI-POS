self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open('pos-cache').then((cache) => cache.addAll([
      '/admin.php',
      '/cashier.php'
    ]))
  );
});

self.addEventListener('fetch', (e) => {
  e.respondWith(
    fetch(e.request).catch(() => caches.match(e.request))
  );
});