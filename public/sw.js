const CACHE_NAME = "todo-app-v2";

const FILES_TO_CACHE = ["./style.css", "./script.js"];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(FILES_TO_CACHE)),
  );

  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys
            .filter((key) => key !== CACHE_NAME)
            .map((key) => caches.delete(key)),
        ),
      ),
  );

  self.clients.claim();
});

self.addEventListener("fetch", (event) => {
  const url = new URL(event.request.url);

  // Jangan cache API atau halaman PHP; data harus selalu berasal dari server.
  if (
    url.pathname.includes("/api/") ||
    url.pathname.endsWith(".php") ||
    event.request.method !== "GET"
  ) {
    event.respondWith(fetch(event.request));
    return;
  }

  // Halaman utama selalu meminta versi terbaru dari server.
  if (event.request.mode === "navigate") {
    event.respondWith(fetch(event.request));
    return;
  }

  // Untuk aset statis, gunakan cache jika tersedia.
  event.respondWith(
    caches.match(event.request).then((cached) => {
      if (cached) return cached;

      return fetch(event.request).then((response) => {
        if (response.ok && url.origin === self.location.origin) {
          const copy = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, copy);
          });
        }

        return response;
      });
    }),
  );
});

self.addEventListener("message", (event) => {
  if (!event.data || event.data.type !== "SCHEDULE_NOTIFICATION") return;

  const todo = event.data.todo;
  const delay = Number(event.data.delay);

  if (!todo || !Number.isFinite(delay) || delay <= 0) return;

  setTimeout(() => {
    self.registration.showNotification("Todo Reminder", {
      body: todo.title,
      tag: `todo-${todo.id}`,
      data: { id: todo.id },
    });
  }, delay);
});

self.addEventListener("notificationclick", (event) => {
  event.notification.close();

  event.waitUntil(
    clients
      .matchAll({
        type: "window",
        includeUncontrolled: true,
      })
      .then((clientList) => {
        for (const client of clientList) {
          if ("focus" in client) return client.focus();
        }

        if (clients.openWindow) return clients.openWindow("./");
      }),
  );
});
