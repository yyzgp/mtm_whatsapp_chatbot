<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'MTM Sales CRM') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased">
    <div class="flex h-full" x-data="{ sidebarOpen: false }">
        <!-- Sidebar -->
        @include('layouts.partials.sidebar')

        <!-- Main Content -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
            @include('layouts.partials.topbar')

            <main class="flex-1 overflow-y-auto bg-gray-50">
                <div class="py-6">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div
        x-data="{ notifications: [], _nid: 0 }"
        @notify.window="
            const n = { id: ++_nid, message: $event.detail.message, type: $event.detail.type || 'success' };
            notifications.push(n);
            setTimeout(() => notifications = notifications.filter(x => x.id !== n.id), 4000);
        "
        class="fixed bottom-4 right-4 z-50 space-y-2 pointer-events-none"
    >
        <template x-for="n in notifications" :key="n.id">
            <div
                x-show="true"
                x-transition:enter="transform ease-out duration-300 transition"
                x-transition:enter-start="translate-y-2 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-sm font-medium max-w-sm pointer-events-auto"
                :class="{
                    'bg-green-600 text-white': n.type === 'success',
                    'bg-red-600 text-white': n.type === 'error',
                    'bg-yellow-500 text-white': n.type === 'warning',
                    'bg-blue-600 text-white': n.type === 'info',
                }"
            >
                <span x-text="n.message"></span>
            </div>
        </template>
    </div>

    @livewireScripts
    @stack('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('notify', (data) => {
                const detail = Array.isArray(data) ? data[0] : data;
                window.dispatchEvent(new CustomEvent('notify', { detail }));
            });
        });
    </script>

    @if(config('services.firebase.project_id'))
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
        import { getMessaging, getToken, onMessage } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging.js';

        const firebaseConfig = {
            apiKey: '{{ config("services.firebase.web_api_key") }}',
            authDomain: '{{ config("services.firebase.project_id") }}.firebaseapp.com',
            projectId: '{{ config("services.firebase.project_id") }}',
            messagingSenderId: '{{ config("services.firebase.messaging_sender_id") }}',
            appId: '{{ config("services.firebase.web_app_id") }}',
        };

        const app = initializeApp(firebaseConfig);

        // Register service worker and pass config
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/firebase-messaging-sw.js').then((reg) => {
                reg.active?.postMessage({ type: 'FIREBASE_CONFIG', config: firebaseConfig });
                navigator.serviceWorker.ready.then((readyReg) => {
                    readyReg.active?.postMessage({ type: 'FIREBASE_CONFIG', config: firebaseConfig });
                });

                const messaging = getMessaging(app);

                Notification.requestPermission().then((permission) => {
                    if (permission === 'granted') {
                        getToken(messaging, { vapidKey: '{{ config("services.firebase.vapid_key") }}', serviceWorkerRegistration: reg })
                            .then((token) => {
                                if (token) {
                                    fetch('/fcm-token', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                        body: JSON.stringify({ token }),
                                    });
                                }
                            })
                            .catch((err) => console.log('FCM token error:', err));
                    }
                });

                // Foreground messages
                onMessage(messaging, (payload) => {
                    const { title, body } = payload.notification || {};
                    const data = payload.data || {};
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: body || title, type: 'info' } }));

                    if (Notification.permission === 'granted') {
                        new Notification(title || 'New Message', { body: body || '', icon: '/favicon.ico', data });
                    }
                });
            });
        }
    </script>
    @endif
</body>
</html>
