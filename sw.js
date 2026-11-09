/*
 * Shop ICT Gadgets – app service worker.
 * - Pages: always fetched fresh from the network (prices and stock change); if offline, the last copy of
 *   that page is shown, or the offline page.
 * - Styles, scripts, fonts and pictures: served from the cache and refreshed in the background.
 * - Never cached: the admin, the cart, checkout, orders, wishlist and the api/ endpoints (personal or live data).
 * Change VERSION to make every phone drop its old cache.
 */
const VERSION = "sict-v3";
const STATIC_CACHE = `${VERSION}-static`;
const PAGE_CACHE = `${VERSION}-pages`;
const OFFLINE_URL = "offline.html";
const PRECACHE = [OFFLINE_URL, "assets/images/logo/logo.png", "assets/images/pwa/icon-192.png", "manifest.json"];
const PRIVATE = /\/(admin|api)\/|\/(cart|checkout|order|wishlist)\.php/;
const MAX_PAGES = 40;
const MAX_STATIC = 250;

self.addEventListener("install", (event) => {
  event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

async function trim(cacheName, max) {
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();
  for (let i = 0; i < keys.length - max; i++) await cache.delete(keys[i]);
}

self.addEventListener("fetch", (event) => {
  const req = event.request;
  if (req.method !== "GET") return;
  const url = new URL(req.url);
  const sameOrigin = url.origin === self.location.origin;
  const isFont = /fonts\.(googleapis|gstatic)\.com$/.test(url.hostname);
  if (!sameOrigin && !isFont) return; // maps, WhatsApp, etc. go straight to the network
  if (sameOrigin && PRIVATE.test(url.pathname)) {
    // Personal pages are never stored; when offline they show the offline page instead of a browser error.
    if (req.mode === "navigate" && !url.pathname.includes("/admin/")) {
      event.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
    }
    return;
  }

  // Pages: network first, then the saved copy, then the offline page.
  if (req.mode === "navigate") {
    event.respondWith(
      fetch(req)
        .then((res) => {
          if (res.ok && res.type === "basic") {
            const copy = res.clone();
            caches.open(PAGE_CACHE).then((c) => c.put(req, copy)).then(() => trim(PAGE_CACHE, MAX_PAGES));
          }
          return res;
        })
        .catch(async () => (await caches.match(req)) || (await caches.match(OFFLINE_URL)))
    );
    return;
  }

  // Styles, scripts, fonts and pictures: cache first, refreshed in the background.
  if (isFont || /\.(css|js|png|jpe?g|webp|gif|svg|ico|woff2?|ttf)$/i.test(url.pathname)) {
    event.respondWith(
      caches.open(STATIC_CACHE).then(async (cache) => {
        const cached = await cache.match(req);
        const network = fetch(req)
          .then((res) => {
            if (res.ok || res.type === "opaque") cache.put(req, res.clone()).then(() => trim(STATIC_CACHE, MAX_STATIC));
            return res;
          })
          .catch(() => cached);
        return cached || network;
      })
    );
  }
});
