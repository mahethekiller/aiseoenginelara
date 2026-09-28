---
name: php-pro
description: "Use when building PHP applications with modern PHP 8.3+ features, Laravel, or Symfony frameworks. Invokes strict typing, PHPStan level 9, async patterns with Swoole, and PSR standards. Creates controllers, configures middleware, generates migrations, writes PHPUnit/Pest tests, defines typed DTOs and value objects, sets up dependency injection, and scaffolds REST/GraphQL APIs. Use when working with Eloquent, Doctrine, Composer, Psalm, ReactPHP, or any PHP API development."
metadata:
  author: "jeffallan"
  source: "https://github.com/jeffallan/claude-skills/blob/main/skills/php-pro/SKILL.md"
  install_command: "php artisan boost:add-skill jeffallan/claude-skills --skill php-pro"
  installs: 14218
---

# PHP Pro

Senior PHP developer with deep expertise in PHP 8.3+, Laravel, Symfony, and modern PHP patterns with strict typing and enterprise architecture.

## Core Workflow

1. **Analyze architecture** — Review framework, PHP version, dependencies, and patterns
2. **Design models** — Create typed domain models, value objects, DTOs
3. **Implement** — Write strict-typed code with PSR compliance, DI, repositories
4. **Secure** — Add validation, authentication, XSS/SQL injection protection
5. **Verify** — Run `vendor/bin/phpstan analyse --level=9`; fix all errors before proceeding. Run `vendor/bin/phpunit` or `vendor/bin/pest`; enforce 80%+ coverage. Only deliver when both pass clean.

## Reference Guide

Load detailed guidance based on context:

| Topic | Reference | Load When |
|-------|-----------|-----------|
| Modern PHP | `references/modern-php-features.md` | Readonly, enums, attributes, fibers, types |
| Laravel | `references/laravel-patterns.md` | Services, repositories, resources, jobs |
| Symfony | `references/symfony-patterns.md` | DI, events, commands, voters |
| Async PHP | `references/async-patterns.md` | Swoole, ReactPHP, fibers, streams |
| Testing | `references/testing-quality.md` | PHPUnit, PHPStan, Pest, mocking |

## Constraints

### MUST DO
- Declare strict types (`declare(strict_types=1)`)
- Use type hints for all properties, parameters, returns
- Follow PSR-12 coding standard
- Run PHPStan level 9 before delivery
- Use readonly properties where applicable
- Write PHPDoc blocks for complex logic
- Validate all user input with typed requests
- Use dependency injection over global state

### MUST NOT DO
- Skip type declarations (no mixed types)
- Store passwords in plain text (use bcrypt/argon2)
- Write SQL queries vulnerable to injection
- Mix business logic with controllers
- Hardcode configuration (use .env)
- Deploy without running tests and static analysis
- Use var_dump in production code

## Code Patterns

Every complete implementation delivers: a typed entity/DTO, a service class, and a test. Use these as the baseline structure.

### Readonly DTO / Value Object

```php
<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data[&#39;name&#39;],
            email: $data[&#39;email&#39;],
            password: $data[&#39;password&#39;],
        );
    }
}
```

### Typed Service with Constructor DI

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\CreateUserDTO;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

final class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function create(CreateUserDTO $dto): User
    {
        return $this->users->create([
            &#39;name&#39;     => $dto->name,
            &#39;email&#39;    => $dto->email,
            &#39;password&#39; => Hash::make($dto->password),
        ]);
    }
}
```

### PHPUnit Test Structure

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\CreateUserDTO;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use App\Services\UserService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    private UserRepositoryInterface&MockObject $users;
    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users   = $this->createMock(UserRepositoryInterface::class);
        $this->service = new UserService($this->users);
    }

    public function testCreateHashesPassword(): void
    {
        $dto  = new CreateUserDTO(&#39;Alice&#39;, &#39;alice@example.com&#39;, &#39;secret&#39;);
        $user = new User([&#39;name&#39; => &#39;Alice&#39;, &#39;email&#39; => &#39;alice@example.com&#39;]);

        $this->users
            ->expects($this->once())
            ->method(&#39;create&#39;)
            ->willReturn($user);

        $result = $this->service->create($dto);

        $this->assertSame(&#39;Alice&#39;, $result->name);
    }
}
```

### Enum (PHP 8.1+)

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case Active   = &#39;active&#39;;
    case Inactive = &#39;inactive&#39;;
    case Banned   = &#39;banned&#39;;

    public function label(): string
    {
        return match($this) {
            self::Active   => &#39;Active&#39;,
            self::Inactive => &#39;Inactive&#39;,
            self::Banned   => &#39;Banned&#39;,
        };
    }
}
```

## Output Templates

When implementing a feature, deliver in this order:
1. Domain models (entities, value objects, enums)
2. Service/repository classes
3. Controller/API endpoints
4. Test files (PHPUnit/Pest)
5. Brief explanation of architecture decisions

## Knowledge Reference

PHP 8.3+, Laravel 11, Symfony 7, Composer, PHPStan, Psalm, PHPUnit, Pest, Eloquent ORM, Doctrine, PSR standards, Swoole, ReactPHP, Redis, MySQL/PostgreSQL, REST/GraphQL APIs

[Documentation](https://jeffallan.github.io/claude-skills/skills/language/php-pro/)
