---
name: eloquent-best-practices
description: "Best practices for Laravel Eloquent ORM including query optimization, relationship management, and avoiding common pitfalls like N+1 queries."
metadata:
  author: "iserter"
  source: "https://github.com/iserter/laravel-claude-agents/blob/main/skills/eloquent-best-practices/SKILL.md"
  install_command: "php artisan boost:add-skill iserter/laravel-claude-agents --skill eloquent-best-practices"
  installs: 1243
---

# Eloquent Best Practices

## Query Optimization

### Always Eager Load Relationships

```php
// ❌ N+1 Query Problem
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->user->name; // N additional queries
}

// ✅ Eager Loading
$posts = Post::with(&#39;user&#39;)->get();
foreach ($posts as $post) {
    echo $post->user->name; // No additional queries
}
```

### Select Only Needed Columns

```php
// ❌ Fetches all columns
$users = User::all();

// ✅ Only needed columns
$users = User::select([&#39;id&#39;, &#39;name&#39;, &#39;email&#39;])->get();

// ✅ With relationships
$posts = Post::with([&#39;user:id,name&#39;])->select([&#39;id&#39;, &#39;title&#39;, &#39;user_id&#39;])->get();
```

### Use Query Scopes

```php
// ✅ Define reusable query logic
class Post extends Model
{
    public function scopePublished($query)
    {
        return $query->where(&#39;status&#39;, &#39;published&#39;)
                    ->whereNotNull(&#39;published_at&#39;);
    }
    
    public function scopePopular($query, $threshold = 100)
    {
        return $query->where(&#39;views&#39;, &#39;>&#39;, $threshold);
    }
}

// Usage
$posts = Post::published()->popular()->get();
```

## Relationship Best Practices

### Define Return Types

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
```

### Use withCount for Counts

```php
// ❌ Triggers additional queries
foreach ($posts as $post) {
    echo $post->comments()->count();
}

// ✅ Load counts efficiently
$posts = Post::withCount(&#39;comments&#39;)->get();
foreach ($posts as $post) {
    echo $post->comments_count;
}
```

## Mass Assignment Protection

```php
class Post extends Model
{
    // ✅ Whitelist fillable attributes
    protected $fillable = [&#39;title&#39;, &#39;content&#39;, &#39;status&#39;];
    
    // Or blacklist guarded attributes
    protected $guarded = [&#39;id&#39;, &#39;user_id&#39;];
    
    // ❌ Never do this
    // protected $guarded = [];
}
```

## Use Casts for Type Safety

```php
class Post extends Model
{
    protected $casts = [
        &#39;published_at&#39; => &#39;datetime&#39;,
        &#39;metadata&#39; => &#39;array&#39;,
        &#39;is_featured&#39; => &#39;boolean&#39;,
        &#39;views&#39; => &#39;integer&#39;,
    ];
}
```

## Chunking for Large Datasets

```php
// ✅ Process in chunks to save memory
Post::chunk(200, function ($posts) {
    foreach ($posts as $post) {
        // Process each post
    }
});

// ✅ Or use lazy collections
Post::lazy()->each(function ($post) {
    // Process one at a time
});
```

## Database-Level Operations

```php
// ❌ Slow - loads into memory first
$posts = Post::where(&#39;status&#39;, &#39;draft&#39;)->get();
foreach ($posts as $post) {
    $post->update([&#39;status&#39; => &#39;archived&#39;]);
}

// ✅ Fast - single query
Post::where(&#39;status&#39;, &#39;draft&#39;)->update([&#39;status&#39; => &#39;archived&#39;]);

// ✅ Increment/decrement
Post::where(&#39;id&#39;, $id)->increment(&#39;views&#39;);
```

## Use Model Events Wisely

```php
class Post extends Model
{
    protected static function booted()
    {
        static::creating(function ($post) {
            $post->slug = Str::slug($post->title);
        });
        
        static::deleting(function ($post) {
            $post->comments()->delete();
        });
    }
}
```

## Common Pitfalls to Avoid

### Don&#39;t Query in Loops

```php
// ❌ Bad
foreach ($userIds as $id) {
    $user = User::find($id);
}

// ✅ Good
$users = User::whereIn(&#39;id&#39;, $userIds)->get();
```

### Don&#39;t Forget Indexes

```php
// Migration
Schema::create(&#39;posts&#39;, function (Blueprint $table) {
    $table->id();
    $table->foreignId(&#39;user_id&#39;)->constrained()->index();
    $table->string(&#39;slug&#39;)->unique();
    $table->string(&#39;status&#39;)->index();
    $table->timestamp(&#39;published_at&#39;)->nullable()->index();
    
    // Composite index for common queries
    $table->index([&#39;status&#39;, &#39;published_at&#39;]);
});
```

### Prevent Lazy Loading in Development

```php
// In AppServiceProvider boot method
Model::preventLazyLoading(!app()->isProduction());
```

## Checklist

- [ ] Relationships eagerly loaded where needed
- [ ] Only selecting required columns
- [ ] Using query scopes for reusability
- [ ] Mass assignment protection configured
- [ ] Appropriate casts defined
- [ ] Indexes on foreign keys and query columns
- [ ] Using database-level operations when possible
- [ ] Chunking for large datasets
- [ ] Model events used appropriately
- [ ] Lazy loading prevented in development
