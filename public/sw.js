const CACHE_NAME = "todo-app-v1";

const APP_SHELL = [
  "./index.php",
  "./offline.html",
  "./style.css",
  "./script.js",
  "./manifest.webmanifest",
  "./icons/icon-192.png",
  "./icons/icon-512.png",
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(async (cache) => {
      for (const url of APP_SHELL) {
        try {
          const response = await fetch(url);

          if (response.ok) {
            await cache.put(url, response);
          }
        } catch (error) {
          console.log("Failed to cache:", url);
        }
      }
    }),
  );

  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name)),
      );
    }),
  );

  self.clients.claim();
});

self.addEventListener("fetch", (event) => {
  const request = event.request;

  const url = new URL(request.url);

  // Only handle GET requests from the same origin.
  if (request.method !== "GET" || url.origin !== self.location.origin) {
    return;
  }

  if (url.pathname.includes("/api/")) {
    event.respondWith(fetch(request));

    return;
  }

  if (request.mode === "navigate") {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response.ok) {
            const responseClone = response.clone();

            caches.open(CACHE_NAME).then((cache) => {
              cache.put("./index.php", responseClone);
            });
          }

          return response;
        })

        .catch(async () => {
          const cache = await caches.open(CACHE_NAME);

          return (
            (await cache.match(request)) ||
            (await cache.match("./index.php")) ||
            (await cache.match("./offline.html"))
          );
        }),
    );

    return;
  }

  event.respondWith(
    caches.match(request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }

      return fetch(request).then((response) => {
        if (response.ok) {
          const responseClone = response.clone();

          caches.open(CACHE_NAME).then((cache) => {
            cache.put(request, responseClone);
          });
        }

        return response;
      });
    }),
  );
});

self.addEventListener("message", (event) => {
  if (event.data?.type !== "SCHEDULE_NOTIFICATION") {
    return;
  }

  const todo = event.data.todo;

  const delay = Number(event.data.delay);

  if (!todo || !Number.isFinite(delay) || delay <= 0) {
    return;
  }

  setTimeout(() => {
    self.registration.showNotification("Todo Reminder", {
      body: todo.title,

      tag: `todo-${todo.id}`,

      data: {
        id: todo.id,
      },
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
          if ("focus" in client) {
            return client.focus();
          }
        }

        return clients.openWindow("./index.php");
      }),
  );
});
