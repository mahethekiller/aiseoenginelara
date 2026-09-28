# Feature Test Template

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_records(): void
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'models.view']);
        $user->givePermissionTo('models.view');

        $response = $this->actingAs($user)->get(route('models.index'));

        $response->assertStatus(200);
    }
}
```