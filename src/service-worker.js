self.addEventListener('install', event => {
    event.waitUntil(caches.open('pokeprof-v3').then((cache) => cache.addAll([
      /*'cards.js',
      'style.css'*/
    ])));
});

self.addEventListener('activate', event => {
    
});

self.addEventListener('fetch', event => {
    event.respondWith(caches.match(event.request).then((response) => response || fetch(event.request)));
});