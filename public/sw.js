// Service worker for Smarter Every Hour push notifications

self.addEventListener('push', (event) => {
    if (!event.data) return;

    let data;
    try {
        data = event.data.json();
    } catch {
        data = { title: 'Smarter Every Hour', body: event.data.text() };
    }

    const options = {
        body: data.body || '',
        icon: data.icon || '/icons/icon-192.png',
        badge: data.badge || '/icons/badge-96.png',
        data: { url: data.url || '/' },
        requireInteraction: false,
        tag: 'smarter-every-hour-lesson',
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'Smarter Every Hour', options)
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
