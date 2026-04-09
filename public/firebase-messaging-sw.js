importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');

// Config is passed via query string when registering the service worker
// Fallback: listen for config message from main thread
let firebaseInitialized = false;

self.addEventListener('message', (event) => {
  if (event.data?.type === 'FIREBASE_CONFIG' && !firebaseInitialized) {
    firebase.initializeApp(event.data.config);
    firebaseInitialized = true;
    const messaging = firebase.messaging();
    messaging.onBackgroundMessage((payload) => {
      const { title, body } = payload.notification || {};
      const data = payload.data || {};
      self.registration.showNotification(title || 'New Message', {
        body: body || '',
        icon: '/favicon.ico',
        data: data,
        tag: data.conversation_id || 'default',
      });
    });
  }
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const data = event.notification.data;
  if (data?.conversation_id) {
    event.waitUntil(clients.openWindow('/chat?conversation=' + data.conversation_id));
  }
});
