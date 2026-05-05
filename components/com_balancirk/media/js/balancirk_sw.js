const CACHE_NAME = 'balancirk-pwa-v1';
const OFFLINE_URLS = [
  '/index.php?option=com_balancirk&view=member',
  '/index.php?option=com_balancirk&view=students',
  '/index.php?option=com_balancirk&view=subscriptions'
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(OFFLINE_URLS)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        const copy = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
        return response;
      })
      .catch(() => caches.match(event.request).then((resp) => resp || caches.match('/index.php?option=com_balancirk&view=member')))
  );
});
