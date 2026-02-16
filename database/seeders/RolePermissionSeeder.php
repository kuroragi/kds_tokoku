<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Define permissions grouped by module
        $permissions = [
            'business-unit.view',
            'business-unit.create',
            'business-unit.edit',
            'business-unit.delete',
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',
            'role.view',
            'role.create',
            'role.edit',
            'role.delete',
            'permission.view',
            'permission.create',
            'permission.edit',
            'permission.delete',
            'coa.view',
            'coa.create',
            'coa.edit',
            'coa.delete',
            'journal.view',
            'journal.create',
            'journal.edit',
            'journal.delete',
            'report.view',
            'report.export',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create default roles
        $roles = [
            'admin' => [
                'business-unit.view', 'business-unit.create', 'business-unit.edit',
                'user.view', 'user.create', 'user.edit',
                'role.view',
                'coa.view', 'coa.create', 'coa.edit',
                'journal.view', 'journal.create', 'journal.edit', 'journal.delete',
                'report.view', 'report.export',
            ],
            'pemilik' => [
                'business-unit.view',
                'user.view',
                'coa.view',
                'journal.view',
                'report.view', 'report.export',
            ],
            'kasir' => [
                'journal.view', 'journal.create',
                'coa.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        // Ensure superadmin has all permissions
        $superadmin = Role::where('name', 'superadmin')->first();
        if ($superadmin) {
            $superadmin->syncPermissions(Permission::all());
        }
    }
}
