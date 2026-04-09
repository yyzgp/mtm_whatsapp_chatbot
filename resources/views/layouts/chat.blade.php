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
<body class="h-full font-sans antialiased overflow-hidden">
    <div class="flex h-full" x-data="{ sidebarOpen: false }">
        <!-- Sidebar -->
        @include('layouts.partials.sidebar')

        <!-- Main Content -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
            @include('layouts.partials.topbar')

            <main class="flex-1 overflow-hidden">
                {{ $slot }}
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
</body>
</html>
