<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Content Intelligence & Generation
            'manage-presets',
            'generate-content',
            'view-content',
            'manage-clients',

            // SERP & Rank Tracking Intelligence
            'track-ranks',
            'view-rank-database',
            'export-rank-data',
            'delete-rank-data',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
