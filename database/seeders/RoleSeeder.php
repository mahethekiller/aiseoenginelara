<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Matrix mapping of roles to permissions
        $rolePermissions = [
            'super_admin' => [
                'manage-presets',
                'generate-content',
                'view-content',
                'manage-clients',
                'track-ranks',
                'view-rank-database',
                'export-rank-data',
                'delete-rank-data',
            ],
            'admin' => [
                'manage-presets',
                'generate-content',
                'view-content',
                'manage-clients',
                'track-ranks',
                'view-rank-database',
                'export-rank-data',
                'delete-rank-data',
            ],
            'seo_specialist' => [
                'track-ranks',
                'view-rank-database',
                'export-rank-data',
                'manage-clients',
            ],
            'editor' => [
                'manage-presets',
                'generate-content',
                'view-content',
                'manage-clients',
                'track-ranks',
                'view-rank-database',
                'export-rank-data',
                'delete-rank-data',
            ],
            'writer' => [
                'generate-content',
                'view-content',
                'track-ranks',
                'view-rank-database',
                'export-rank-data',
            ],
            'viewer' => [
                'view-content',
                'view-rank-database',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            // Ensure permissions exist before syncing
            foreach ($perms as $permName) {
                Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            }

            $role->syncPermissions($perms);
        }
    }
}
