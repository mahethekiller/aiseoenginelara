# Seeder Template

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'models.view',
            'models.create',
            'models.edit',
            'models.delete',
        ];

        foreach ($permissions as $p) {
            Permission::findOrCreate($p);
        }

        $admin = Role::findOrCreate('Super Admin');
        $admin->givePermissionTo($permissions);
    }
}
```