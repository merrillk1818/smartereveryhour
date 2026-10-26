// Register service worker and handle push subscription UI

const PUSH_DISMISSED_KEY = 'push_banner_dismissed';

async function registerServiceWorker() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return null;
    }
    try {
        const reg = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
        return reg;
    } catch (err) {
        console.error('Service worker registration failed:', err);
        return null;
    }
}

async function getVapidPublicKey() {
    const res = await fetch('/push/vapid-key');
    const data = await res.json();
    return data.key;
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
}

async function subscribeToPush(registration) {
    const publicKey = await getVapidPublicKey();
    if (!publicKey) return null;

    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(publicKey),
    });

    const keys = subscription.toJSON().keys;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    await fetch('/push/subscribe', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            endpoint: subscription.endpoint,
            p256dh: keys.p256dh,
            auth: keys.auth,
        }),
    });

    return subscription;
}

async function checkCurrentSubscription(registration) {
    return registration.pushManager.getSubscription();
}

async function initPushUI() {
    const banner = document.getElementById('push-banner');
    const enableBtn = document.getElementById('push-enable-btn');
    const dismissBtn = document.getElementById('push-dismiss-btn');

    if (!banner || !enableBtn) return;

    const registration = await registerServiceWorker();
    if (!registration) return;

    // Already subscribed — hide banner
    const existing = await checkCurrentSubscription(registration);
    if (existing) return;

    // User already dismissed banner
    if (sessionStorage.getItem(PUSH_DISMISSED_KEY)) return;

    // Don't show if permission already denied
    if (Notification.permission === 'denied') return;

    banner.style.display = 'flex';

    enableBtn.addEventListener('click', async () => {
        banner.style.display = 'none';
        try {
            await subscribeToPush(registration);
        } catch (err) {
            console.error('Push subscription failed:', err);
        }
    });

    dismissBtn.addEventListener('click', () => {
        banner.style.display = 'none';
        sessionStorage.setItem(PUSH_DISMISSED_KEY, '1');
    });
}

document.addEventListener('DOMContentLoaded', initPushUI);
