<header class="bg-white shadow-sm border-b border-gray-200 flex-shrink-0">
    <div class="flex items-center justify-between h-16 px-4 sm:px-6">
        <!-- Mobile menu button -->
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <!-- Page title -->
        <div class="hidden lg:block">
            <h1 class="text-lg font-semibold text-gray-900">
                @yield('page-title', config('app.name'))
            </h1>
        </div>

        <!-- Right side -->
        <div class="flex items-center gap-3 ml-auto">
            <!-- Profile link -->
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900">
                <img src="{{ auth()->user()->avatar_url }}" alt="avatar" class="w-8 h-8 rounded-full">
                <span class="hidden sm:block font-medium">{{ auth()->user()->name }}</span>
            </a>
        </div>
    </div>
</header>
