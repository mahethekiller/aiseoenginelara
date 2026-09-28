@php
    $currentUser = auth()->user();
    $activeClient = $currentUser ? \App\Models\Client::find($currentUser->active_client_id) : null;
    $allClients = \App\Models\Client::where('is_active', true)->orderBy('name')->get();
    $activePreset = \App\Models\AiPreset::where('is_active', true)->first() 
        ?? \App\Models\AiPreset::where('user_id', $currentUser?->id)->where('is_active', true)->first();
@endphp

<header class="navbar bg-base-100 border-b border-base-300 px-4 min-h-16 sticky top-0 z-30 shadow-xs">
    <!-- Left Section: Mobile Drawer Toggle & Breadcrumb -->
    <div class="navbar-start flex items-center gap-3">
        <label for="app-drawer" class="btn btn-square btn-ghost btn-sm lg:hidden" aria-label="Open Sidebar">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </label>
        <div class="flex items-center gap-2">
            <span class="font-bold text-base tracking-tight hidden sm:inline-block">
                @yield('page_title', 'AI SEO Engine')
            </span>
            @hasSection('page_badge')
                <span class="badge badge-primary badge-outline badge-sm font-mono">@yield('page_badge')</span>
            @endif
        </div>
    </div>

    <!-- Center Section: Active Client Quick Switcher -->
    <div class="navbar-center hidden md:flex items-center gap-2">
        <div class="flex items-center bg-base-200/80 rounded-lg p-1 border border-base-300">
            <span class="text-xs font-semibold px-2.5 text-base-content/70 flex items-center gap-1.5">
                <i data-lucide="building-2" class="w-3.5 h-3.5 text-primary"></i> Client:
            </span>
            <select id="global-client-switcher" onchange="switchGlobalClient(this.value)" class="select select-ghost select-xs text-xs font-medium focus:outline-none">
                <option value="" {{ !$activeClient ? 'selected' : '' }}>None (Generic Mode)</option>
                @foreach($allClients as $client)
                    <option value="{{ $client->id }}" {{ $activeClient && $activeClient->id === $client->id ? 'selected' : '' }}>
                        {{ $client->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Right Section: Model Badge, Theme Toggle & Profile -->
    <div class="navbar-end flex items-center gap-2">
        <!-- Active LLM Preset Badge -->
        <a href="{{ route('settings.index') }}" class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-mono bg-base-200 border border-base-300 hover:border-primary/50 transition-colors" title="Active Model Preset">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-base-content/80 font-semibold">{{ $activePreset ? $activePreset->model : 'gemini-2.0-flash' }}</span>
        </a>

        <!-- Dark / Light Mode Toggle Button -->
        <button type="button" onclick="toggleTheme()" class="btn btn-ghost btn-circle btn-sm" title="Toggle Dark/Light Mode" aria-label="Toggle theme">
            <i id="theme-icon-sun" data-lucide="sun" class="w-4 h-4 text-amber-400 hidden"></i>
            <i id="theme-icon-moon" data-lucide="moon" class="w-4 h-4 text-indigo-400"></i>
        </button>

        <!-- User Profile Dropdown -->
        @auth
        <div class="dropdown dropdown-end ml-1">
            <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar placeholder btn-sm border border-base-300">
                <div class="bg-primary text-primary-content rounded-full w-8 text-xs font-bold">
                    {{ strtoupper(substr($currentUser->name ?? 'A', 0, 1)) }}
                </div>
            </div>
            <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-50 p-2 shadow-xl bg-base-100 border border-base-300 rounded-box w-56">
                <li class="menu-title px-3 py-1">
                    <span class="font-bold text-sm text-base-content">{{ $currentUser->name }}</span>
                    <span class="text-xs text-base-content/60 font-mono">{{ $currentUser->email }}</span>
                    <span class="badge badge-neutral badge-xs mt-1">{{ $currentUser->getRoleNames()->first() ?? 'User' }}</span>
                </li>
                <div class="divider my-1"></div>
                <li><a href="{{ route('settings.index') }}"><i data-lucide="settings" class="w-4 h-4"></i> Settings</a></li>
                @role('super_admin|admin')
                    <li><a href="{{ route('users.index') }}"><i data-lucide="users" class="w-4 h-4"></i> Manage Users</a></li>
                @endrole
                <div class="divider my-1"></div>
                <li>
                    <form method="POST" action="{{ route('logout') }}" class="w-full p-0">
                        @csrf
                        <button type="submit" class="text-error flex items-center gap-2 w-full px-3 py-2 text-left">
                            <i data-lucide="log-out" class="w-4 h-4"></i> Sign Out
                        </button>
                    </form>
                </li>
            </ul>
        </div>
        @else
        <a href="{{ route('login') }}" class="btn btn-primary btn-sm">Sign In</a>
        @endauth
    </div>
</header>

<script>
    function switchGlobalClient(clientId) {
        $.ajax({
            url: "{{ route('client.switch') }}",
            type: 'POST',
            data: { client_id: clientId },
            success: function(res) {
                showToast(res.message || 'Client switched successfully', 'success');
                setTimeout(() => window.location.reload(), 300);
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Failed to switch client', 'error');
            }
        });
    }
</script>
