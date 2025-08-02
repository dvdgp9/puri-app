const CACHE_NAME = 'puri-v1';
const ASSETS_TO_CACHE = [
  '/',
  '/index.php',
  '/actividades.php',
  '/asistencia.php',
  '/informes.php',
  '/instalaciones.php',
  '/public/assets/css/style.css',
  '/public/assets/icons/icon-192x192.png',
  '/public/assets/icons/icon-512x512.png',
  '/public/assets/icons/icon-maskable.png',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
];

// Instalación del Service Worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        return cache.addAll(ASSETS_TO_CACHE);
      })
  );
});

// Activación del Service Worker
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Estrategia de cacheo: Network First, fallback to cache
self.addEventListener('fetch', (event) => {
  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Si la respuesta es válida, la guardamos en caché
        if (response.status === 200) {
          const responseClone = response.clone();
          caches.open(CACHE_NAME)
            .then((cache) => {
              cache.put(event.request, responseClone);
            });
        }
        return response;
      })
      .catch(() => {
        // Si falla la red, intentamos recuperar de caché
        return caches.match(event.request)
          .then((response) => {
            if (response) {
              return response;
            }
            // Si no está en caché y no hay red, mostramos una página offline
            if (event.request.mode === 'navigate') {
              return caches.match('/');
            }
            return new Response('Sin conexión', {
              status: 503,
              statusText: 'Service Unavailable'
            });
          });
      })
  );
}); 