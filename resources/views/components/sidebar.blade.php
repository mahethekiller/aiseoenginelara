<aside class="w-64 min-h-full bg-base-100 border-r border-base-300 flex flex-col justify-between p-3 select-none">
    <div>
        <!-- App Logo & Branding -->
        <div class="px-3 py-4 mb-2 flex items-center justify-between border-b border-base-300/70">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-lg border border-primary/20">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                </div>
                <div>
                    <div class="font-extrabold text-sm tracking-tight text-base-content leading-none">AI SEO Engine</div>
                    <div class="text-[10px] text-base-content/60 font-medium">Content Intelligence</div>
                </div>
            </a>
            <span class="badge badge-primary badge-xs font-mono font-bold tracking-wider uppercase">Pro</span>
        </div>

        <!-- Navigation Menu -->
        <ul class="menu menu-sm gap-1 px-1">
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard*') ? 'active bg-primary/15 text-primary font-bold shadow-xs border-l-2 border-primary' : 'text-base-content/75 hover:bg-base-200/80 hover:text-base-content' }} flex items-center gap-2.5 py-2 rounded-lg transition-all">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 text-indigo-500"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="menu-title text-[11px] font-bold uppercase tracking-wider text-base-content/50 px-2 py-1 mt-1">
                Content Engine
            </li>
            @can('generate-content')
            <li>
                <a href="{{ route('blog.creator') }}" class="{{ request()->routeIs('blog.creator*') ? 'active bg-primary/15 text-primary font-bold shadow-xs border-l-2 border-primary' : 'text-base-content/75 hover:bg-base-200/80 hover:text-base-content' }} flex items-center gap-2.5 py-2 rounded-lg transition-all">
                    <i data-lucide="pen-tool" class="w-4 h-4 text-primary"></i>
                    <span>SEO Blog Creator</span>
                </a>
            </li>
            @endcan
            @can('manage-clients')
            <li>
                <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.*') ? 'active bg-primary/15 text-primary font-bold shadow-xs border-l-2 border-primary' : 'text-base-content/75 hover:bg-base-200/80 hover:text-base-content' }} flex items-center gap-2.5 py-2 rounded-lg transition-all">
                    <i data-lucide="building-2" class="w-4 h-4 text-emerald-500"></i>
                    <span>Agency Clients</span>
                </a>
            </li>
            @endcan
            @can('manage-presets')
            <li>
                <a href="{{ route('prompt-templates.index') }}" class="{{ request()->routeIs('prompt-templates.*') ? 'active bg-primary/15 text-primary font-bold shadow-xs border-l-2 border-primary' : 'text-base-content/75 hover:bg-base-200/80 hover:text-base-content' }} flex items-center gap-2.5 py-2 rounded-lg transition-all">
                    <i data-lucide="file-code-2" class="w-4 h-4 text-violet-500"></i>
                    <span>Prompt Blueprints</span>
                </a>
            </li>
            <li>
                <a href="{{ url('/ai-presets') }}" class="{{ request()->is('ai-presets*') ? 'active bg-primary/15 text-primary font-bold shadow-xs border-l-2 border-primary' : 'text-base-content/75 hover:bg-base-200/80 hover:text-base-content' }} flex items-center gap-2.5 py-2 rounded-lg transition-all">
                    <i data-lucide="sliders" class="w-4 h-4 text-fuchsia-500"></i>
                    <span>AI Presets</span>
                </a>
            </li>
            @endcan
            @can('generate-content')
            <li>
                <a href="{{ route('rewriter.index') }}" class="{{ request()->routeIs('rewriter.*') ? 'active bg-primary/15 text-primary font-bold shadow-xs border-l-2 border-primary' : 'text-base-content/75 hover:bg-base-200/80 hover:text-base-content' }} flex items-center gap-2.5 py-2 rounded-lg transition-all">
                    <i data-lucide="refresh-cw" class="w-4 h-4 text-amber-500"></i>
                    <span>Rewriter Studio</span>
                </a>
            </li>
            @endcan
            @can('view-content')
            <li>
                <a href="{{ route('articles.index') }}" class="{{ request()->routeIs('articles.*') ? 'active bg-primary/15 text-primary font-bold shadow-xs border-l-2 border-primary' : 'text-base-content/75 hover:bg-base-200/80 hover:text-base-content' }} flex items-center gap-2.5 py-2 rounded-lg transition-all">
                    <i data-lucide="database" class="w-4 h-4 text-cyan-500"></i>
                    <span>Content Database</span>
                </a>
            </li>
            @endcan
            @can('track-ranks')
            <li>
                <a href="{{ route('rank-tracker.index') }}" class="{{ request()->routeIs('rank-tracker.index') || (request()->routeIs('rank-tracker.*') && !request()->routeIs('rank-tracker.database*')) ? 'active bg-primary/15 text-primary font-bold shadow-xs border-l-2 border-primary' : 'text-base-content/75 hover:bg-base-200/80 hover:text-base-content' }} flex items-center gap-2.5 py-2 rounded-lg transition-all">
                    <i data-lucide="trending-up" class="w-4 h-4 text-rose-500"></i>
                    <span>Rank Tracker</span>
                    <span class="badge badge-primary badge-outline badge-xs font-mono ml-auto">Top 50</span>
                </a>
            </li>
            @endcan
            @can('view-rank-database')
            <li>
                <a href="{{ route('rank-tracker.database') }}" class="{{ request()->routeIs('rank-tracker.database*') ? 'active bg-primary/15 text-primary font-bold shadow-xs border-l-2 border-primary' : 'text-base-content/75 hover:bg-base-200/80 hover:text-base-content' }} flex items-center gap-2.5 py-2 rounded-lg transition-all">
                    <i data-lucide="database" class="w-4 h-4 text-emerald-500"></i>
                    <span>Rank Database</span>
                    <span class="badge badge-accent badge-outline badge-xs font-mono ml-auto">Archive</span>
                </a>
            </li>
            @endcan

            @role('super_admin|admin')
            <div class="divider my-2 opacity-50"></div>

            <li class="menu-title text-[11px] font-bold uppercase tracking-wider text-base-content/50 px-2 py-1">
                Administration
            </li>
            <li>
                <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active bg-primary/15 text-primary font-bold shadow-xs border-l-2 border-primary' : 'text-base-content/75 hover:bg-base-200/80 hover:text-base-content' }} flex items-center gap-2.5 py-2 rounded-lg transition-all">
                    <i data-lucide="settings-2" class="w-4 h-4 text-slate-400"></i>
                    <span>Settings & Presets</span>
                </a>
            </li>
            <li>
                <a href="{{ route('reports.usage') }}" class="{{ request()->routeIs('reports.*') ? 'active bg-primary/15 text-primary font-bold shadow-xs border-l-2 border-primary' : 'text-base-content/75 hover:bg-base-200/80 hover:text-base-content' }} flex items-center gap-2.5 py-2 rounded-lg transition-all">
                    <i data-lucide="bar-chart-3" class="w-4 h-4 text-blue-400"></i>
                    <span>Token Cost Reports</span>
                </a>
            </li>
            <li>
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active bg-primary/15 text-primary font-bold shadow-xs border-l-2 border-primary' : 'text-base-content/75 hover:bg-base-200/80 hover:text-base-content' }} flex items-center gap-2.5 py-2 rounded-lg transition-all">
                    <i data-lucide="users" class="w-4 h-4 text-rose-400"></i>
                    <span>User Management</span>
                </a>
            </li>
            <li>
                <a href="{{ route('permissions.index') }}" class="{{ request()->routeIs('permissions.*') ? 'active bg-primary/15 text-primary font-bold shadow-xs border-l-2 border-primary' : 'text-base-content/75 hover:bg-base-200/80 hover:text-base-content' }} flex items-center gap-2.5 py-2 rounded-lg transition-all">
                    <i data-lucide="shield-check" class="w-4 h-4 text-indigo-400"></i>
                    <span>Role Permissions</span>
                </a>
            </li>
            @endrole
        </ul>
    </div>

    <!-- Bottom Status Card -->
    <div class="p-3 bg-base-200/60 rounded-xl border border-base-300">
        <div class="flex items-center justify-between mb-1.5">
            <span class="text-[11px] font-semibold text-base-content/70 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> LLM Core Status
            </span>
            <span class="text-[10px] font-mono text-emerald-500 font-bold uppercase">100% Live</span>
        </div>
        <p class="text-[11px] text-base-content/60 leading-tight">
            Gemini, Claude, OpenAI & DeepSeek live provider sync active.
        </p>
    </div>
</aside>
