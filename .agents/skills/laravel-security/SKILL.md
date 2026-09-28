---
name: laravel-security
description: "Laravel security best practices — authentication, authorization, Eloquent safety, CSRF, XSS prevention, API security, and secure deployment configurations."
metadata:
  author: "affaan-m"
  source: "https://github.com/affaan-m/ecc/blob/main/skills/laravel-security/SKILL.md"
  install_command: "php artisan boost:add-skill affaan-m/ecc --skill laravel-security"
  installs: 8291
---

# Laravel Security Best Practices

Comprehensive security guidelines for Laravel applications to protect against common vulnerabilities.

## When to Activate

- Setting up Laravel authentication and authorization (Sanctum, Passport, Jetstream, Breeze)
- Implementing user roles, permissions, and policies
- Configuring production security settings and environment variables
- Reviewing Laravel applications for security vulnerabilities
- Deploying Laravel applications to production
- Writing secure Eloquent queries and migrations

## Production Configuration

### Essential Production Settings

```php
// config/app.php
&#39;env&#39; => env(&#39;APP_ENV&#39;, &#39;production&#39;),
&#39;debug&#39; => (bool) env(&#39;APP_DEBUG&#39;, false), // CRITICAL: Never true in production
&#39;key&#39; => env(&#39;APP_KEY&#39;), // Must be set: php artisan key:generate

// config/session.php
&#39;secure&#39; => env(&#39;SESSION_SECURE_COOKIE&#39;, true),
&#39;http_only&#39; => true,
&#39;same_site&#39; => &#39;lax&#39;,

// Verify APP_KEY is set at boot
// bootstrap/app.php or a service provider
if (empty(config(&#39;app.key&#39;))) {
    throw new RuntimeException(&#39;APP_KEY is not set. Run: php artisan key:generate&#39;);
}
```

### Environment File Security

```bash
# NEVER commit .env to version control
# .gitignore already includes .env by default

# Use .env.example with placeholders instead
DB_PASSWORD=
APP_KEY=
SANCTUM_TOKEN_PREFIX=

# Validate required variables at boot
// In AppServiceProvider::boot()
$requiredKeys = [&#39;app.key&#39;, &#39;database.connections.mysql.database&#39;, &#39;database.connections.mysql.username&#39;];
foreach ($requiredKeys as $key) {
    if (empty(config($key))) {
        throw new RuntimeException("Missing required config key: {$key}");
    }
}
```

### HTTPS Enforcement

```php
// AppServiceProvider::boot() or middleware
if (app()->environment(&#39;production&#39;)) {
    URL::forceScheme(&#39;https&#39;);
    request()->server->set(&#39;HTTPS&#39;, &#39;on&#39;);
}

// config/app.php for trusted proxies (load balancers)
// Use specific IP ranges — * trusts all, allowing X-Forwarded-* spoofing
// AWS: &#39;10.0.0.0/8&#39;, &#39;172.16.0.0/12&#39;, &#39;192.168.0.0/16&#39;
&#39;trusted_proxies&#39; => [&#39;10.0.0.0/8&#39;, &#39;172.16.0.0/12&#39;],

// Force HTTPS in production via middleware
// app/Http/Middleware/ForceHttps.php
public function handle($request, Closure $next)
{
    if (!$request->secure() && app()->environment(&#39;production&#39;)) {
        return redirect()->secure($request->getRequestUri());
    }
    return $next($request);
}
```

## Authentication

### Sanctum (API Token Authentication)

```php
// config/sanctum.php
&#39;stateful&#39; => explode(&#39;,&#39;, env(&#39;SANCTUM_STATEFUL_DOMAINS&#39;, sprintf(
    &#39;%s%s&#39;,
    &#39;localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1&#39;,
    env(&#39;APP_URL&#39;) ? &#39;,&#39; . parse_url(env(&#39;APP_URL&#39;), PHP_URL_HOST) : &#39;&#39;
)));

&#39;expiration&#39; => 60 * 24, // Token expiration in minutes (null = never)
&#39;token_prefix&#39; => env(&#39;SANCTUM_TOKEN_PREFIX&#39;, &#39;&#39;),

// Issuing tokens with abilities
$token = $user->createToken(&#39;api-token&#39;, [&#39;read&#39;, &#39;write&#39;])->plainTextToken;

// Validate abilities on routes
Route::middleware(&#39;auth:sanctum&#39;)->group(function () {
    Route::get(&#39;/orders&#39;, function () {
        // User must have &#39;read&#39; ability
        abort_unless(Auth::user()->tokenCan(&#39;read&#39;), 403);
        // ...
    })->middleware(&#39;abilities:read&#39;);

    Route::post(&#39;/orders&#39;, function () {
        // User must have &#39;write&#39; ability
        abort_unless(Auth::user()->tokenCan(&#39;write&#39;), 403);
        // ...
    })->middleware(&#39;abilities:write&#39;);
});
```

### Password Security

```php
// config/hashing.php
// Default is bcrypt. Argon2id is stronger.
&#39;bcrypt&#39; => [
    &#39;rounds&#39; => env(&#39;BCRYPT_ROUNDS&#39;, 12), // Increase for stronger hashing
],

&#39;argon&#39; => [
    &#39;memory&#39; => 65536,
    &#39;threads&#39; => 4,
    &#39;time&#39; => 4,
],

// Password validation in RegisterRequest
public function rules(): array
{
    return [
        &#39;password&#39; => [
            &#39;required&#39;,
            &#39;confirmed&#39;,
            Password::min(12)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(), // Checks haveibeenpwned
        ],
    ];
}

// Rate limit login attempts
// App\Http\Controllers\Auth\AuthenticatedSessionController
protected function authenticated(Request $request, $user)
{
    if ($user->wasRecentlyLockedOut()) {
        // Notify user of suspicious login
        $user->notify(new SuspiciousLoginNotification($request->ip()));
    }
}
```

### Session Management

```php
// config/session.php
&#39;driver&#39; => env(&#39;SESSION_DRIVER&#39;, &#39;database&#39;), // database/redis > file
&#39;lifetime&#39; => env(&#39;SESSION_LIFETIME&#39;, 120),
&#39;expire_on_close&#39; => env(&#39;SESSION_EXPIRE_ON_CLOSE&#39;, false),
&#39;encrypt&#39; => env(&#39;SESSION_ENCRYPT&#39;, false),

// Regenerate session on login
// App\Http\Controllers\Auth\AuthenticatedSessionController
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();
    $request->session()->regenerate(); // CRITICAL: prevents session fixation
    return redirect()->intended(RouteServiceProvider::HOME);
}

// Invalidate session on logout
public function destroy(Request $request): RedirectResponse
{
    Auth::guard(&#39;web&#39;)->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect(&#39;/&#39;);
}
```

## Authorization

### Gates

```php
// App\Providers\AuthServiceProvider
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define(&#39;update-post&#39;, function (User $user, Post $post): bool {
        return $user->id === $post->user_id;
    });

    Gate::define(&#39;publish-post&#39;, function (User $user): bool {
        return $user->role === &#39;editor&#39; || $user->role === &#39;admin&#39;;
    });

    // Using before() for super-admin override
    Gate::before(function (User $user, string $ability): ?bool {
        if ($user->role === &#39;super-admin&#39;) {
            return true; // Grants all abilities
        }
        return null; // Fall through to normal checks
    });
}

// Usage in controllers
public function update(Request $request, Post $post): RedirectResponse
{
    Gate::authorize(&#39;update-post&#39;, $post);
    // Or: $this->authorize(&#39;update-post&#39;, $post);
    // Or: abort_unless(Auth::user()->can(&#39;update-post&#39;, $post), 403);
    // ...
}
```

### Policies

```php
// App\Policies\PostPolicy
class PostPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true; // Public listing
    }

    public function view(?User $user, Post $post): bool
    {
        return $post->is_published || ($user && $user->id === $post->user_id);
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail(); // Must verify email first
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id && $post->created_at->diffInDays(now()) <= 30;
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->role === &#39;admin&#39;;
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->role === &#39;super-admin&#39;;
    }
}

// Register in AuthServiceProvider
protected $policies = [
    Post::class => PostPolicy::class,
];

// Controller usage
public function show(Post $post): View
{
    $this->authorize(&#39;view&#39;, $post);
    return view(&#39;posts.show&#39;, compact(&#39;post&#39;));
}

// Blade usage
@can(&#39;update&#39;, $post)
    <a href="{{ route(&#39;posts.edit&#39;, $post) }}">Edit</a>
@endcan

@cannot(&#39;update&#39;, $post)
    <span>You cannot edit this post</span>
@endcannot
```

### Middleware Authorization

```php
// Using middleware in routes
Route::put(&#39;/posts/{post}&#39;, [PostController::class, &#39;update&#39;])
    ->middleware(&#39;can:update,post&#39;);

Route::get(&#39;/posts/create&#39;, [PostController::class, &#39;create&#39;])
    ->middleware(&#39;can:create,App\Models\Post&#39;);

// Custom authorization middleware
// app/Http/Middleware/CheckRole.php
class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): mixed
    {
        if (!$request->user() || $request->user()->role !== $role) {
            abort(403, &#39;Unauthorized. This area requires role: &#39; . $role);
        }
        return $next($request);
    }
}

// Register in Kernel
protected $routeMiddleware = [
    &#39;role&#39; => \App\Http\Middleware\CheckRole::class,
];

// Route usage
Route::middleware([&#39;auth&#39;, &#39;role:admin&#39;])->group(function () {
    Route::get(&#39;/admin&#39;, [AdminController::class, &#39;index&#39;]);
});
```

## Eloquent Security

### Mass Assignment Protection

```php
// BAD: $guarded = [] allows ALL columns to be mass-assigned
// NEVER use $guarded = [] in production

// GOOD: Whitelist fillable attributes
final class User extends Authenticatable
{
    protected $fillable = [
        &#39;name&#39;,
        &#39;email&#39;,
        &#39;phone&#39;,
        &#39;avatar&#39;,
    ];
    // NEVER add &#39;role&#39;, &#39;is_admin&#39;, &#39;is_verified&#39; here
}

// GOOD: Explicitly control which fields can be filled in requests
public function store(StoreUserRequest $request): RedirectResponse
{
    $user = User::create($request->safe()->only([
        &#39;name&#39;, &#39;email&#39;, &#39;phone&#39;, &#39;avatar&#39;
    ]));
    // $request->safe() uses validated data only
    // $request->only() is NOT safe on its own without validation rules
}

// BAD: Creating a user with request data directly
User::create($request->all()); // VULNERABLE to mass assignment!

// BETTER: Use DTOs for creation
$user = User::create($request->validated()); // Only validated fields
```

### SQL Injection Prevention

```php
// GOOD: Eloquent automatically parameterizes queries
User::where(&#39;email&#39;, $userInput)->first();
User::whereRaw(&#39;email = ?&#39;, [$userInput])->first();

// GOOD: Query Builder also parameterizes
DB::table(&#39;users&#39;)->where(&#39;email&#39;, $userInput)->first();
DB::select(&#39;SELECT * FROM users WHERE email = ?&#39;, [$userInput]);

// BAD: Raw string interpolation
DB::select("SELECT * FROM users WHERE email = &#39;{$userInput}&#39;"); // VULNERABLE!
User::whereRaw("email = &#39;{$userInput}&#39;")->first(); // VULNERABLE!

// BAD: whereRaw/orderByRaw with unescaped input
User::orderByRaw($userInput); // VULNERABLE!
User::groupByRaw($userInput); // VULNERABLE!

// BAD: DB::statement with concatenation
DB::statement("INSERT INTO users (email) VALUES (&#39;{$userInput}&#39;)"); // VULNERABLE!
```

### Attribute Casting

```php
final class User extends Authenticatable
{
    protected $casts = [
        &#39;email_verified_at&#39; => &#39;datetime&#39;,
        &#39;is_admin&#39; => &#39;boolean&#39;, // Cast to boolean prevents string injection
        &#39;settings&#39; => &#39;array&#39;, // Automatically json_encode/json_decode
        &#39;metadata&#39; => &#39;encrypted:array&#39;, // Laravel 11+ encrypted casting
        &#39;password&#39; => &#39;hashed&#39;, // Laravel 10+ auto-hashes on set
    ];
}
```

### Model Security

```php
final class User extends Authenticatable
{
    // Hide sensitive attributes from JSON/API responses
    protected $hidden = [
        &#39;password&#39;,
        &#39;remember_token&#39;,
        &#39;two_factor_secret&#39;,
        &#39;two_factor_recovery_codes&#39;,
    ];

    // Append only safe computed attributes
    protected $appends = [&#39;full_name&#39;]; // safe
    // NEVER append sensitive computed data
}

final class Post extends Model
{
    // Global scope to filter soft deleted records
    use SoftDeletes;

    // Prevent N+1 by restricting lazy loading (optional strict mode)
    // AppServiceProvider::boot()
    // Model::preventLazyLoading(!app()->isProduction());
}
```

## CSRF Protection

### Default Protection

```php
// Laravel CSRF is enabled by default via VerifyCsrfToken middleware
// app/Http/Kernel.php (protected $middlewareGroups[&#39;web&#39;])

// All POST/PUT/PATCH/DELETE forms must include @csrf
<form method="POST" action="/posts">
    @csrf
    <input type="text" name="title">
    <button type="submit">Create</button>
</form>
```

### Excluding Routes (Carefully)

```php
// app/Http/Middleware/VerifyCsrfToken.php
class VerifyCsrfToken extends Middleware
{
    // Only exclude routes that have external CSRF protection (webhooks, etc.)
    protected $except = [
        &#39;stripe/*&#39;, // Stripe webhooks use their own signature verification
        // Avoid blanket &#39;api/*&#39; — stateful Sanctum routes need CSRF.
        // Exclude only specific stateless webhook/endpoint routes.
    ];
}
```

### CSRF with JavaScript

```html
<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
// Axios example (Laravel ships with Axios)
axios.defaults.headers.common[&#39;X-CSRF-TOKEN&#39;] = document.querySelector(
    &#39;meta[name="csrf-token"]&#39;
).getAttribute(&#39;content&#39;);

// Fetch example
fetch(&#39;/posts&#39;, {
    method: &#39;POST&#39;,
    headers: {
        &#39;X-CSRF-TOKEN&#39;: document.querySelector(&#39;meta[name="csrf-token"]&#39;).getAttribute(&#39;content&#39;),
        &#39;Content-Type&#39;: &#39;application/json&#39;,
    },
    body: JSON.stringify(data),
});
</script>
```

## XSS Prevention

### Blade Templating Security

```blade
{{-- SAFE: Auto-escaped by Blade --}}
{{ $userInput }}

{{-- DANGEROUS: Raw output — NEVER use with user input --}}
{!! $userInput !!}

{{-- SAFE: Only use {!! !!} with trusted content you control --}}
{!! $trustedHtmlFromYourServer !!}

{{-- GOOD: Use specific escaping directives --}}
@js($data) {{-- JSON encode for JavaScript --}}
@json($data) {{-- JSON encode in templates --}}

{{-- BAD: Direct user input in raw HTML --}}
<div>{!! $user->bio !!}</div> {{-- VULNERABLE if user provides bio --}}
```

### Safe HTML Handling

```php
// When you must allow some HTML, use a whitelist approach
use HTMLPurifier; // Requires: composer require ezyang/htmlpurifier

public function sanitizeHtml(string $dirty): string
{
    $config = \HTMLPurifier_Config::createDefault();
    $config->set(&#39;HTML.Allowed&#39;, &#39;p,b,i,a[href],ul,ol,li,br&#39;);
    $config->set(&#39;URI.AllowedSchemes&#39;, [&#39;http&#39;, &#39;https&#39;, &#39;mailto&#39;]);
    $purifier = new \HTMLPurifier($config);
    return $purifier->purify($dirty);
}

// In blade:
<div>{!! $sanitizedContent !!}</div> {{-- Safe after purification --}}
```

### JavaScript Context Escaping

```blade
{{-- SAFE: Blade @js escapes for JavaScript context --}}
<script>
    const user = @js($user); // JSON + escaped for JS context
    const settings = @json($settings); // Direct JSON encode
</script>

{{-- DANGEROUS: Manual JSON in JS context --}}
<script>
    const user = {{ json_encode($user) }}; // NOT escaped for JS!
</script>
```

### HTTP Headers for XSS Protection

```php
// App\Http\Middleware\SecurityHeaders.php
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        $response->headers->set(&#39;X-Content-Type-Options&#39;, &#39;nosniff&#39;);
        $response->headers->set(&#39;X-Frame-Options&#39;, &#39;DENY&#39;);
        $response->headers->set(&#39;X-XSS-Protection&#39;, &#39;1; mode=block&#39;);
        $response->headers->set(&#39;Referrer-Policy&#39;, &#39;strict-origin-when-cross-origin&#39;);
        $response->headers->set(
            &#39;Content-Security-Policy&#39;,
            "default-src &#39;self&#39;; script-src &#39;self&#39;; style-src &#39;self&#39; &#39;unsafe-inline&#39;; img-src &#39;self&#39; data: https:; font-src &#39;self&#39;; connect-src &#39;self&#39;; frame-ancestors &#39;none&#39;"
        );

        return $response;
    }
}

// Register in kernel
protected $middleware = [
    \App\Http\Middleware\SecurityHeaders::class,
];
```

## Input Validation

### Form Request Validation

```php
final class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(&#39;create&#39;, Post::class) ?? false;
    }

    public function rules(): array
    {
        return [
            &#39;title&#39; => [&#39;required&#39;, &#39;string&#39;, &#39;max:255&#39;, &#39;sanitize_html&#39;],
            &#39;content&#39; => [&#39;required&#39;, &#39;string&#39;, &#39;max:10000&#39;],
            &#39;image&#39; => [
                &#39;required&#39;,
                &#39;image&#39;,
                &#39;mimes:jpg,jpeg,png,gif,webp&#39;, // Whitelist specific types
                &#39;max:2048&#39;, // 2MB max
            ],
            &#39;tags&#39; => [&#39;array&#39;],
            &#39;tags.*&#39; => [&#39;integer&#39;, &#39;exists:tags,id&#39;],
        ];
    }

    public function messages(): array
    {
        return [
            &#39;title.max&#39; => &#39;Post title must not exceed 255 characters.&#39;,
            &#39;image.max&#39; => &#39;Image must be under 2MB.&#39;,
        ];
    }

    // Sanitize input after validation
    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated();
        $validated[&#39;title&#39;] = strip_tags($validated[&#39;title&#39;]);
        return $key ? ($validated[$key] ?? $default) : $validated;
    }
}
```

### Custom Validation Rules

```php
// app/Rules/StrongPassword.php
class StrongPassword implements Rule
{
    public function passes($attribute, $value): bool
    {
        return preg_match(&#39;/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^()_\-+=])[A-Za-z\d@$!%*?&#^()_\-+=]{12,}$/&#39;, $value);
    }

    public function message(): string
    {
        return &#39;The :attribute must be at least 12 characters with uppercase, lowercase, number, and symbol.&#39;;
    }
}

// app/Rules/NotBlacklistedDomain.php
class NotBlacklistedDomain implements Rule
{
    private array $blacklisted = [&#39;mailinator.com&#39;, &#39;guerrillamail.com&#39;];

    public function passes($attribute, $value): bool
    {
        $domain = substr(strrchr($value, &#39;@&#39;), 1);
        return !in_array(strtolower($domain), $this->blacklisted);
    }

    public function message(): string
    {
        return &#39;Email from disposable domains is not allowed.&#39;;
    }
}
```

## API Security

### Rate Limiting

```php
// App/Providers/RouteServiceProvider
protected function configureRateLimiting(): void
{
    RateLimiter::for(&#39;api&#39;, function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for(&#39;auth&#39;, function (Request $request) {
        return Limit::perMinute(5)->by($request->ip())
            ->response(function () {
                return response()->json([
                    &#39;message&#39; => &#39;Too many login attempts. Try again in 1 minute.&#39;,
                ], 429);
            });
    });

    RateLimiter::for(&#39;uploads&#39;, function (Request $request) {
        return Limit::perHour(10)->by($request->user()?->id ?? $request->ip())
            ->response(function () {
                return response()->json([
                    &#39;message&#39; => &#39;Upload limit reached. Try again later.&#39;,
                ], 429);
            });
    });
}

// Route usage
Route::middleware([&#39;auth:sanctum&#39;, &#39;throttle:api&#39;])->group(function () {
    Route::apiResource(&#39;posts&#39;, PostController::class);
});

Route::post(&#39;/login&#39;, [AuthController::class, &#39;login&#39;])
    ->middleware(&#39;throttle:auth&#39;);
```

### API Authentication — Sanctum vs Passport

```php
// Sanctum (recommended for most apps — simple, first-party, SPA)
// config/sanctum.php
&#39;expiration&#39; => 60 * 24, // Tokens expire after 24 hours
&#39;model&#39; => User::class,

// Issuing scoped tokens
$token = $user->createToken(&#39;client-name&#39;, [
    &#39;posts:read&#39;,
    &#39;posts:write&#39;,
])->plainTextToken;

// Middleware scoping
Route::middleware(&#39;auth:sanctum&#39;)->group(function () {
    Route::get(&#39;/posts&#39;, [PostController::class, &#39;index&#39;])
        ->middleware(&#39;abilities:posts:read&#39;);

    Route::post(&#39;/posts&#39;, [PostController::class, &#39;store&#39;])
        ->middleware(&#39;abilities:posts:write&#39;);
});

// Passport (OAuth2 — for third-party clients or complex auth flows)
// Install: composer require laravel/passport
Passport::tokensExpireIn(now()->addDays(15));
Passport::refreshTokensExpireIn(now()->addDays(30));
Passport::personalAccessTokensExpireIn(now()->addMonths(6));
```

### CORS Configuration

```php
// config/cors.php
return [
    &#39;paths&#39; => [&#39;api/*&#39;, &#39;sanctum/csrf-cookie&#39;],
    &#39;allowed_methods&#39; => [&#39;*&#39;],
    &#39;allowed_origins&#39; => explode(&#39;,&#39;, env(&#39;CORS_ALLOWED_ORIGINS&#39;, &#39;&#39;)), // Whitelist specific origins
    &#39;allowed_origins_patterns&#39; => [],
    &#39;allowed_headers&#39; => [&#39;*&#39;],
    &#39;exposed_headers&#39; => [&#39;X-Total-Count&#39;, &#39;X-Pagination-Page&#39;],
    &#39;max_age&#39; => 0,
    &#39;supports_credentials&#39; => true, // Required for Sanctum SPA auth
];

// NEVER: Allow all origins in production unless absolutely necessary
// &#39;allowed_origins&#39; => [&#39;*&#39;], // Only for truly public APIs
```

## File Upload Security

### Validation

```php
public function rules(): array
{
    return [
        &#39;document&#39; => [
            &#39;required&#39;,
            &#39;file&#39;,
            &#39;mimes:pdf,doc,docx,xls,xlsx&#39;, // Whitelist specific MIME types
            &#39;max:10240&#39;, // 10MB
            &#39;extensions:pdf,doc,docx,xls,xlsx&#39;, // Verify extension matches MIME
        ],
        &#39;avatar&#39; => [
            &#39;nullable&#39;,
            &#39;image&#39;, // Ensures it&#39;s a valid image
            &#39;mimes:jpg,jpeg,png,webp&#39;,
            &#39;max:2048&#39;,
            &#39;dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000&#39;,
        ],
    ];
}
```

### Secure Storage

```php
// Store files outside public directory
$path = $request->file(&#39;document&#39;)->store(&#39;documents&#39;, &#39;local&#39;);
// Never use &#39;public&#39; disk for sensitive documents

// Use signed URLs for temporary file access
use Illuminate\Support\Facades\Storage;

public function download(Request $request, string $path)
{
    // Generate temporary signed URL (expires in 15 minutes)
    $url = Storage::temporaryUrl($path, now()->addMinutes(15));

    // Validate user has permission
    $this->authorize(&#39;download&#39;, $path);

    return redirect($url);
}

// Storage configuration for cloud with encryption
// config/filesystems.php
&#39;s3&#39; => [
    &#39;driver&#39; => &#39;s3&#39;,
    &#39;key&#39; => env(&#39;AWS_ACCESS_KEY_ID&#39;),
    &#39;secret&#39; => env(&#39;AWS_SECRET_ACCESS_KEY&#39;),
    &#39;region&#39; => env(&#39;AWS_DEFAULT_REGION&#39;),
    &#39;bucket&#39; => env(&#39;AWS_BUCKET&#39;),
    &#39;url&#39; => env(&#39;AWS_URL&#39;),
    &#39;endpoint&#39; => env(&#39;AWS_ENDPOINT&#39;),
    &#39;use_path_style_endpoint&#39; => env(&#39;AWS_USE_PATH_STYLE_ENDPOINT&#39;, false),
    &#39;throw&#39; => false,
    &#39;server_side_encryption&#39; => &#39;AES256&#39;, // Encrypt at rest
],
```

## Dependencies and Secrets

### Composer Security

```bash
# Always audit dependencies in CI
composer audit

# Pin major versions in composer.json
"laravel/framework": "^11.0",
"spatie/laravel-permission": "^6.0"

# Check for abandoned packages
composer why-not

# Keep lock file in version control (it pins exact versions)
# Run `composer update` deliberately, never in CI/CD
```

### Secret Management

```bash
# .env file (NEVER commit)
# .gitignore includes .env by default

APP_KEY=base64:abc123...
DB_PASSWORD=secure_password
STRIPE_KEY=sk_live_...
SANCTUM_TOKEN_PREFIX=myapp_

# For production: Use a secret manager
# Deploy with: env $(aws secretsmanager get-secret-value --secret-id prod/db | jq ...) php artisan serve

# Validate secrets at boot (AppServiceProvider::boot)
$secrets = [&#39;services.stripe.key&#39;, &#39;services.stripe.webhook_secret&#39;];
foreach ($secrets as $key) {
    if (empty(config($key))) {
        Log::critical("Missing secret: {$key}");
    }
}
```

## Queue Security

```php
// Define a named rate limiter (typically in AppServiceProvider::boot())
RateLimiter::for(&#39;payments&#39;, fn () => Limit::perMinute(5));
```

```php
// Encrypt sensitive job data by implementing the interface
final class ProcessPaymentJob implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $paymentIntentId, // Public IDs are fine
        private readonly string $cardFingerprint, // Encrypted via ShouldBeEncrypted
    ) {}

    public function handle(): void
    {
        // Process payment
    }

    // Limit retries and delay between attempts
    public function retryUntil(): Carbon
    {
        return now()->addMinutes(5);
    }

    // Rate limit how many jobs of this type can run
    public function middleware(): array
    {
        return [
            new RateLimited(&#39;payments&#39;),
        ];
    }
}
```

## Logging Security Events

```php
// config/logging.php
&#39;channels&#39; => [
    &#39;security&#39; => [
        &#39;driver&#39; => &#39;single&#39;,
        &#39;path&#39; => storage_path(&#39;logs/security.log&#39;),
        &#39;level&#39; => &#39;warning&#39;,
    ],
],

// Audit log helper
final class SecurityLogger
{
    public static function log(string $event, array $context = []): void
    {
        Log::channel(&#39;security&#39;)->warning($event, array_merge([
            &#39;user_id&#39; => Auth::id(),
            &#39;ip&#39; => request()->ip(),
            &#39;user_agent&#39; => request()->userAgent(),
            &#39;url&#39; => request()->fullUrl(),
            &#39;timestamp&#39; => now()->toIso8601String(),
        ], $context));
    }
}

// Usage
SecurityLogger::log(&#39;failed_login_attempt&#39;, [&#39;email&#39; => $email]);
SecurityLogger::log(&#39;password_change&#39;);
SecurityLogger::log(&#39;role_change&#39;, [&#39;target_user&#39; => $targetId, &#39;new_role&#39; => &#39;admin&#39;]);
SecurityLogger::log(&#39;suspicious_activity&#39;, [&#39;reason&#39; => &#39;multiple_attempts_from_different_ips&#39;]);
```

## Quick Security Checklist

| Check | Description |
|-------|-------------|
| `APP_DEBUG=false` | Never run with debug enabled in production |
| `APP_KEY` set | Always run `php artisan key:generate` |
| HTTPS enforced | Force HTTPS in production via middleware or proxy |
| `$fillable` whitelisted | Never use `$guarded = []` |
| CSRF active | `@csrf` on all state-changing forms |
| Sanctum/Passport configured | API authentication with token abilities/scopes |
| Rate limiting applied | Throttle API and auth endpoints |
| Input validation | FormRequest with specific rules, never `$request->all()` |
| File upload restrictions | Validate MIME types, size, dimensions |
| `composer audit` in CI | Check dependencies for known vulnerabilities |
| `password_hash` / `password_verify` | Use Laravel&#39;s built-in hashing (bcrypt/Argon2) |
| Session regeneration on login | Call `$request->session()->regenerate()` |
| Security headers middleware | CSP, X-Frame-Options, X-Content-Type-Options |
| Logged security events | Audit log for auth failures, role changes, suspicious activity |
| `.env` not committed | Verify `.gitignore` includes `.env` |

## Related Skills

- `laravel-patterns` — Laravel architecture, routing, Eloquent, and API patterns
- `backend-patterns` — General backend API and database patterns
- `laravel-tdd` — Laravel testing with PHPUnit and Pest
