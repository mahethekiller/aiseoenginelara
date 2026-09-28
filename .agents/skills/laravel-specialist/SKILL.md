---
name: laravel-specialist
description: "Build and configure Laravel 10+ applications, including creating Eloquent models and relationships, implementing Sanctum authentication, configuring Horizon queues, designing RESTful APIs with API resources, and building reactive interfaces with Livewire. Use when creating Laravel models, setting up queue workers, implementing Sanctum auth flows, building Livewire components, optimising Eloquent queries, or writing Pest/PHPUnit tests for Laravel features."
metadata:
  author: "jeffallan"
  source: "https://github.com/jeffallan/claude-skills/blob/main/skills/laravel-specialist/SKILL.md"
  install_command: "php artisan boost:add-skill jeffallan/claude-skills --skill laravel-specialist"
  installs: 21637
---

# Laravel Specialist

Senior Laravel specialist with deep expertise in Laravel 10+, Eloquent ORM, and modern PHP 8.2+ development.

## Core Workflow

1. **Analyse requirements** — Identify models, relationships, APIs, and queue needs
2. **Design architecture** — Plan database schema, service layers, and job queues
3. **Implement models** — Create Eloquent models with relationships, scopes, and casts; run `php artisan make:model` and verify with `php artisan migrate:status`
4. **Build features** — Develop controllers, services, API resources, and jobs; run `php artisan route:list` to verify routing
5. **Test thoroughly** — Write feature and unit tests; run `php artisan test` before considering any step complete (target >85% coverage)

## Reference Guide

Load detailed guidance based on context:

| Topic | Reference | Load When |
|-------|-----------|-----------|
| Eloquent ORM | `references/eloquent.md` | Models, relationships, scopes, query optimization |
| Routing & APIs | `references/routing.md` | Routes, controllers, middleware, API resources |
| Queue System | `references/queues.md` | Jobs, workers, Horizon, failed jobs, batching |
| Livewire | `references/livewire.md` | Components, wire:model, actions, real-time |
| Testing | `references/testing.md` | Feature tests, factories, mocking, Pest PHP |

## Constraints

### MUST DO
- Use PHP 8.2+ features (readonly, enums, typed properties)
- Type hint all method parameters and return types
- Use Eloquent relationships properly (avoid N+1 with eager loading)
- Implement API resources for transforming data
- Queue long-running tasks
- Write comprehensive tests (>85% coverage)
- Use service containers and dependency injection
- Follow PSR-12 coding standards

### MUST NOT DO
- Use raw queries without protection (SQL injection)
- Skip eager loading (causes N+1 problems)
- Store sensitive data unencrypted
- Mix business logic in controllers
- Hardcode configuration values
- Skip validation on user input
- Use deprecated Laravel features
- Ignore queue failures

## Code Templates

Use these as starting points for every implementation.

### Eloquent Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [&#39;title&#39;, &#39;body&#39;, &#39;status&#39;, &#39;user_id&#39;];

    protected $casts = [
        &#39;status&#39; => PostStatus::class, // backed enum
        &#39;published_at&#39; => &#39;immutable_datetime&#39;,
    ];

    // Relationships — always eager-load via ::with() at call site
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, &#39;user_id&#39;);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // Local scope
    public function scopePublished(Builder $query): Builder
    {
        return $query->where(&#39;status&#39;, PostStatus::Published);
    }
}
```

### Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(&#39;posts&#39;, function (Blueprint $table): void {
            $table->id();
            $table->foreignId(&#39;user_id&#39;)->constrained()->cascadeOnDelete();
            $table->string(&#39;title&#39;);
            $table->text(&#39;body&#39;);
            $table->string(&#39;status&#39;)->default(&#39;draft&#39;);
            $table->timestamp(&#39;published_at&#39;)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(&#39;posts&#39;);
    }
};
```

### API Resource

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            &#39;id&#39;           => $this->id,
            &#39;title&#39;        => $this->title,
            &#39;body&#39;         => $this->body,
            &#39;status&#39;       => $this->status->value,
            &#39;published_at&#39; => $this->published_at?->toIso8601String(),
            &#39;author&#39;       => new UserResource($this->whenLoaded(&#39;author&#39;)),
            &#39;comments&#39;     => CommentResource::collection($this->whenLoaded(&#39;comments&#39;)),
        ];
    }
}
```

### Queued Job

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class PublishPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly Post $post,
    ) {}

    public function handle(): void
    {
        $this->post->update([
            &#39;status&#39;       => PostStatus::Published,
            &#39;published_at&#39; => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        // Log or notify — never silently swallow failures
        logger()->error(&#39;PublishPost failed&#39;, [&#39;post&#39; => $this->post->id, &#39;error&#39; => $e->getMessage()]);
    }
}
```

### Feature Test (Pest)

```php
<?php

use App\Models\Post;
use App\Models\User;

it(&#39;returns a published post for authenticated users&#39;, function (): void {
    $user = User::factory()->create();
    $post = Post::factory()->published()->for($user, &#39;author&#39;)->create();

    $response = $this->actingAs($user)
        ->getJson("/api/posts/{$post->id}");

    $response->assertOk()
        ->assertJsonPath(&#39;data.status&#39;, &#39;published&#39;)
        ->assertJsonPath(&#39;data.author.id&#39;, $user->id);
});

it(&#39;queues a publish job when a draft is submitted&#39;, function (): void {
    Queue::fake();
    $user = User::factory()->create();
    $post = Post::factory()->draft()->for($user, &#39;author&#39;)->create();

    $this->actingAs($user)
        ->postJson("/api/posts/{$post->id}/publish")
        ->assertAccepted();

    Queue::assertPushed(PublishPost::class, fn ($job) => $job->post->is($post));
});
```

## Validation Checkpoints

Run these at each workflow stage to confirm correctness before proceeding:

| Stage | Command | Expected Result |
|-------|---------|-----------------|
| After migration | `php artisan migrate:status` | All migrations show `Ran` |
| After routing | `php artisan route:list --path=api` | New routes appear with correct verbs |
| After job dispatch | `php artisan queue:work --once` | Job processes without exception |
| After implementation | `php artisan test --coverage` | >85% coverage, 0 failures |
| Before PR | `./vendor/bin/pint --test` | PSR-12 linting passes |

## Knowledge Reference

Laravel 10+, Eloquent ORM, PHP 8.2+, API resources, Sanctum/Passport, queues, Horizon, Livewire, Inertia, Octane, Pest/PHPUnit, Redis, broadcasting, events/listeners, notifications, task scheduling

[Documentation](https://jeffallan.github.io/claude-skills/skills/backend/laravel-specialist/)
