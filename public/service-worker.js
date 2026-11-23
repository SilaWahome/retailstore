// Service worker disabled — immediately unregisters itself so pages always load fresh
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', () => {
    self.registration.unregister();
    caches.keys().then(keys => keys.forEach(key => caches.delete(key)));
    self.clients.claim();
});
