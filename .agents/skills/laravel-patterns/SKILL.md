---
name: laravel-patterns
description: "Laravel architecture patterns, routing/controllers, Eloquent ORM, service layers, queues, events, caching, and API resources for production apps. Use when building or reviewing Laravel apps — controllers, Eloquent, service layers, queues, or API resources."
metadata:
  author: "affaan-m"
  source: "https://github.com/affaan-m/ecc/blob/main/skills/laravel-patterns/SKILL.md"
  install_command: "php artisan boost:add-skill affaan-m/ecc --skill laravel-patterns"
  installs: 10109
---

# Laravel Development Patterns

Production-grade Laravel architecture patterns for scalable, maintainable applications.

## When to Use

- Building Laravel web applications or APIs
- Structuring controllers, services, and domain logic
- Working with Eloquent models and relationships
- Designing APIs with resources and pagination
- Adding queues, events, caching, and background jobs

## How It Works

- Structure the app around clear boundaries (controllers -> services/actions -> models).
- Use explicit bindings and scoped bindings to keep routing predictable; still enforce authorization for access control.
- Favor typed models, casts, and scopes to keep domain logic consistent.
- Keep IO-heavy work in queues and cache expensive reads.
- Centralize config in `config/*` and keep environments explicit.

## Examples

### Project Structure

Use a conventional Laravel layout with clear layer boundaries (HTTP, services/actions, models).

### Recommended Layout

```
app/
├── Actions/            # Single-purpose use cases
├── Console/
├── Events/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/       # Form request validation
│   └── Resources/      # API resources
├── Jobs/
├── Models/
├── Policies/
├── Providers/
├── Services/           # Coordinating domain services
└── Support/
config/
database/
├── factories/
├── migrations/
└── seeders/
resources/
├── views/
└── lang/
routes/
├── api.php
├── web.php
└── console.php
```

### Controllers -> Services -> Actions

Keep controllers thin. Put orchestration in services and single-purpose logic in actions.

```php
final class CreateOrderAction
{
    public function __construct(private OrderRepository $orders) {}

    public function handle(CreateOrderData $data): Order
    {
        return $this->orders->create($data);
    }
}

final class OrdersController extends Controller
{
    public function __construct(private CreateOrderAction $createOrder) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->createOrder->handle($request->toDto());

        return response()->json([
            &#39;success&#39; => true,
            &#39;data&#39; => OrderResource::make($order),
            &#39;error&#39; => null,
            &#39;meta&#39; => null,
        ], 201);
    }
}
```

### Routing and Controllers

Prefer route-model binding and resource controllers for clarity.

```php
use Illuminate\Support\Facades\Route;

Route::middleware(&#39;auth:sanctum&#39;)->group(function () {
    Route::apiResource(&#39;projects&#39;, ProjectController::class);
});
```

### Route Model Binding (Scoped)

Use scoped bindings to prevent cross-tenant access.

```php
Route::scopeBindings()->group(function () {
    Route::get(&#39;/accounts/{account}/projects/{project}&#39;, [ProjectController::class, &#39;show&#39;]);
});
```

### Nested Routes and Binding Names

- Keep prefixes and paths consistent to avoid double nesting (e.g., `conversation` vs `conversations`).
- Use a single parameter name that matches the bound model (e.g., `{conversation}` for `Conversation`).
- Prefer scoped bindings when nesting to enforce parent-child relationships.

```php
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware(&#39;auth:sanctum&#39;)->prefix(&#39;conversations&#39;)->group(function () {
    Route::post(&#39;/&#39;, [ConversationController::class, &#39;store&#39;])->name(&#39;conversations.store&#39;);

    Route::scopeBindings()->group(function () {
        Route::get(&#39;/{conversation}&#39;, [ConversationController::class, &#39;show&#39;])
            ->name(&#39;conversations.show&#39;);

        Route::post(&#39;/{conversation}/messages&#39;, [MessageController::class, &#39;store&#39;])
            ->name(&#39;conversation-messages.store&#39;);

        Route::get(&#39;/{conversation}/messages/{message}&#39;, [MessageController::class, &#39;show&#39;])
            ->name(&#39;conversation-messages.show&#39;);
    });
});
```

If you want a parameter to resolve to a different model class, define explicit binding. For custom binding logic, use `Route::bind()` or implement `resolveRouteBinding()` on the model.

```php
use App\Models\AiConversation;
use Illuminate\Support\Facades\Route;

Route::model(&#39;conversation&#39;, AiConversation::class);
```

### Service Container Bindings

Bind interfaces to implementations in a service provider for clear dependency wiring.

```php
use App\Repositories\EloquentOrderRepository;
use App\Repositories\OrderRepository;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
    }
}
```

### Eloquent Model Patterns

### Model Configuration

```php
final class Project extends Model
{
    use HasFactory;

    protected $fillable = [&#39;name&#39;, &#39;owner_id&#39;, &#39;status&#39;];

    protected $casts = [
        &#39;status&#39; => ProjectStatus::class,
        &#39;archived_at&#39; => &#39;datetime&#39;,
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, &#39;owner_id&#39;);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull(&#39;archived_at&#39;);
    }
}
```

### Custom Casts and Value Objects

Use enums or value objects for strict typing.

```php
use Illuminate\Database\Eloquent\Casts\Attribute;

protected $casts = [
    &#39;status&#39; => ProjectStatus::class,
];
```

```php
protected function budgetCents(): Attribute
{
    return Attribute::make(
        get: fn (int $value) => Money::fromCents($value),
        set: fn (Money $money) => $money->toCents(),
    );
}
```

### Eager Loading to Avoid N+1

```php
$orders = Order::query()
    ->with([&#39;customer&#39;, &#39;items.product&#39;])
    ->latest()
    ->paginate(25);
```

### Query Objects for Complex Filters

```php
final class ProjectQuery
{
    public function __construct(private Builder $query) {}

    public function ownedBy(int $userId): self
    {
        $query = clone $this->query;

        return new self($query->where(&#39;owner_id&#39;, $userId));
    }

    public function active(): self
    {
        $query = clone $this->query;

        return new self($query->whereNull(&#39;archived_at&#39;));
    }

    public function builder(): Builder
    {
        return $this->query;
    }
}
```

### Global Scopes and Soft Deletes

Use global scopes for default filtering and `SoftDeletes` for recoverable records.
Use either a global scope or a named scope for the same filter, not both, unless you intend layered behavior.

```php
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

final class Project extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(&#39;active&#39;, function (Builder $builder): void {
            $builder->whereNull(&#39;archived_at&#39;);
        });
    }
}
```

### Query Scopes for Reusable Filters

```php
use Illuminate\Database\Eloquent\Builder;

final class Project extends Model
{
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where(&#39;owner_id&#39;, $userId);
    }
}

// In service, repository etc.
$projects = Project::ownedBy($user->id)->get();
```

### Transactions for Multi-Step Updates

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function (): void {
    $order->update([&#39;status&#39; => &#39;paid&#39;]);
    $order->items()->update([&#39;paid_at&#39; => now()]);
});
```

### Migrations

### Naming Convention

- File names use timestamps: `YYYY_MM_DD_HHMMSS_create_users_table.php`
- Migrations use anonymous classes (no named class); the filename communicates intent
- Table names are `snake_case` and plural by default

### Example Migration

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(&#39;orders&#39;, function (Blueprint $table): void {
            $table->id();
            $table->foreignId(&#39;customer_id&#39;)->constrained()->cascadeOnDelete();
            $table->string(&#39;status&#39;, 32)->index();
            $table->unsignedInteger(&#39;total_cents&#39;);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(&#39;orders&#39;);
    }
};
```

### Form Requests and Validation

Keep validation in form requests and transform inputs to DTOs.

```php
use App\Models\Order;

final class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(&#39;create&#39;, Order::class) ?? false;
    }

    public function rules(): array
    {
        return [
            &#39;customer_id&#39; => [&#39;required&#39;, &#39;integer&#39;, &#39;exists:customers,id&#39;],
            &#39;items&#39; => [&#39;required&#39;, &#39;array&#39;, &#39;min:1&#39;],
            &#39;items.*.sku&#39; => [&#39;required&#39;, &#39;string&#39;],
            &#39;items.*.quantity&#39; => [&#39;required&#39;, &#39;integer&#39;, &#39;min:1&#39;],
        ];
    }

    public function toDto(): CreateOrderData
    {
        return new CreateOrderData(
            customerId: (int) $this->validated(&#39;customer_id&#39;),
            items: $this->validated(&#39;items&#39;),
        );
    }
}
```

### API Resources

Keep API responses consistent with resources and pagination.

```php
$projects = Project::query()->active()->paginate(25);

return response()->json([
    &#39;success&#39; => true,
    &#39;data&#39; => ProjectResource::collection($projects->items()),
    &#39;error&#39; => null,
    &#39;meta&#39; => [
        &#39;page&#39; => $projects->currentPage(),
        &#39;per_page&#39; => $projects->perPage(),
        &#39;total&#39; => $projects->total(),
    ],
]);
```

### Events, Jobs, and Queues

- Emit domain events for side effects (emails, analytics)
- Use queued jobs for slow work (reports, exports, webhooks)
- Prefer idempotent handlers with retries and backoff

### Caching

- Cache read-heavy endpoints and expensive queries
- Invalidate caches on model events (created/updated/deleted)
- Use tags when caching related data for easy invalidation

### Configuration and Environments

- Keep secrets in `.env` and config in `config/*.php`
- Use per-environment config overrides and `config:cache` in production
