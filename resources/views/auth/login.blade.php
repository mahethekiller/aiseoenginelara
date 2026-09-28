<!DOCTYPE html>
<html lang="en" data-theme="night">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - AI SEO Engine</title>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'night';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-base-200 min-h-screen flex items-center justify-center p-4">
    <!-- Top-right theme toggle -->
    <div class="fixed top-4 right-4">
        <button type="button" onclick="toggleTheme()" class="btn btn-circle btn-ghost btn-sm" aria-label="Toggle theme">
            <i id="theme-icon-sun" data-lucide="sun" class="w-4 h-4 text-amber-400 hidden"></i>
            <i id="theme-icon-moon" data-lucide="moon" class="w-4 h-4 text-indigo-400"></i>
        </button>
    </div>

    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-primary/10 text-primary border border-primary/20 mb-3 shadow-sm">
                <i data-lucide="sparkles" class="w-6 h-6"></i>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">AI SEO Engine</h1>
            <p class="text-xs text-base-content/60 font-medium mt-1">Enterprise Content Intelligence Platform</p>
        </div>

        <!-- Login Card -->
        <div class="card bg-base-100 border border-base-300 shadow-xl rounded-2xl p-6 sm:p-8">
            <h2 class="text-lg font-bold text-base-content mb-1">Welcome back</h2>
            <p class="text-xs text-base-content/60 mb-6">Enter your credentials to access your SEO workspace.</p>

            @if ($errors->any())
                <div class="alert alert-error text-xs py-2.5 px-3 rounded-lg mb-4 flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="label py-1 text-xs font-semibold text-base-content/80">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="input input-bordered input-sm w-full bg-base-200/50 text-base-content focus:input-primary text-sm @error('email') input-error @enderror"
                           placeholder="admin@example.com" />
                </div>

                <div>
                    <div class="flex items-center justify-between py-1">
                        <label class="label p-0 text-xs font-semibold text-base-content/80">Password</label>
                    </div>
                    <input type="password" name="password" required
                           class="input input-bordered input-sm w-full bg-base-200/50 text-base-content focus:input-primary text-sm @error('password') input-error @enderror"
                           placeholder="••••••••" />
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="checkbox checkbox-primary checkbox-xs" />
                        <span class="text-base-content/70">Remember me</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit" onclick="submitWithLoader(this)"
                            class="btn btn-primary btn-sm w-full font-bold shadow-xs">
                        Sign In to Engine
                    </button>
                </div>
            </form>

            <div class="divider my-4 text-[10px] text-base-content/40 uppercase tracking-wider font-mono">100% Live Execution</div>

            <div class="bg-base-200/50 rounded-lg p-2.5 border border-base-300/50 text-[11px] text-base-content/70 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                <span>Dual Theme (Night & Light) • Multi-Provider LLM Integration</span>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide && window.lucide.createIcons) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
