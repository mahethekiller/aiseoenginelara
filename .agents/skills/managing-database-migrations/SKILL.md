---
name: managing-database-migrations
description: Standardizes writing safe, robust, and reversible Laravel database migrations. Covers column types, index optimization, foreign key constraints, and rollback handling. Use when creating or modifying database schema tables.
---

# Managing Database Migrations

## When to use this skill
- Adding new database tables (`database/migrations/*.php`).
- Adding, altering, or dropping columns on existing tables.
- Adding database indexes, composite keys, or foreign key constraints.

## Workflow Checklist
- [ ] Name migration files accurately (e.g. `create_attendances_table`, `add_status_to_users_table`).
- [ ] Define correct column data types (e.g. `string`, `bigInteger`, `timestamp`, `json`).
- [ ] Set up foreign key constraints with appropriate cascade actions (`onDelete('cascade')` or `nullOnDelete()`).
- [ ] Add indexes (`$table->index(...)`) for columns frequently queried in `WHERE` or `ORDER BY` clauses.
- [ ] Ensure `down()` method cleanly rolls back changes.

## Code Template

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->string('status', 20)->default('present');
            $table->json('location_metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
```

## Guidelines
- Never modify existing committed migration files that have already run on production/shared environments; create a new migration instead.
- Use `foreignId()` helper for clean foreign key declarations.
