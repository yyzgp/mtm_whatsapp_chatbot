<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    private array $permissions = [
        'customers' => [
            'customers.view' => 'View Customers',
            'customers.view_all' => 'View All Customers',
            'customers.create' => 'Create Customer',
            'customers.edit' => 'Edit Customer',
            'customers.delete' => 'Delete Customer',
            'customers.assign' => 'Assign Customer',
            'customers.export' => 'Export Customers',
            'customers.import' => 'Import Customers',
        ],
        'chat' => [
            'chat.view' => 'View Chat',
            'chat.view_all' => 'View All Chats',
            'chat.reply' => 'Reply to Chat',
            'chat.assign' => 'Assign Chat',
            'chat.manage_ai' => 'Manage AI Settings',
        ],
        'users' => [
            'users.view' => 'View Users',
            'users.view_all' => 'View All Users',
            'users.create' => 'Create User',
            'users.edit' => 'Edit User',
            'users.delete' => 'Delete User',
        ],
        'roles' => [
            'roles.view' => 'View Roles',
            'roles.create' => 'Create Role',
            'roles.edit' => 'Edit Role',
            'roles.delete' => 'Delete Role',
        ],
        'reports' => [
            'reports.view' => 'View Reports',
            'reports.export' => 'Export Reports',
        ],
        'settings' => [
            'settings.view' => 'View Settings',
            'settings.edit' => 'Edit Settings',
        ],
        'teams' => [
            'teams.view' => 'View Teams',
            'teams.create' => 'Create Team',
            'teams.edit' => 'Edit Team',
            'teams.delete' => 'Delete Team',
        ],
        'dashboard' => [
            'dashboard.view' => 'View Dashboard',
        ],
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        foreach ($this->permissions as $module => $modulePermissions) {
            foreach ($modulePermissions as $name => $displayName) {
                Permission::updateOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['module' => $module, 'display_name' => $displayName]
                );
            }
        }

        // Admin role - all permissions
        $admin = Role::updateOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['display_name' => 'Administrator', 'description' => 'Full system access']
        );
        $admin->syncPermissions(Permission::all());

        // Sales Manager role
        $manager = Role::updateOrCreate(
            ['name' => 'sales_manager', 'guard_name' => 'web'],
            ['display_name' => 'Sales Manager', 'description' => 'Manage team and view all data']
        );
        $manager->syncPermissions([
            'dashboard.view',
            'customers.view', 'customers.view_all', 'customers.create',
            'customers.edit', 'customers.assign', 'customers.export',
            'chat.view', 'chat.view_all', 'chat.reply', 'chat.assign', 'chat.manage_ai',
            'users.view', 'users.view_all',
            'teams.view', 'teams.create', 'teams.edit',
            'reports.view', 'reports.export',
            'settings.view',
        ]);

        // Sales Agent role
        $agent = Role::updateOrCreate(
            ['name' => 'sales_agent', 'guard_name' => 'web'],
            ['display_name' => 'Sales Agent', 'description' => 'Handle assigned customers and chats']
        );
        $agent->syncPermissions([
            'dashboard.view',
            'customers.view', 'customers.create', 'customers.edit',
            'chat.view', 'chat.reply',
            'users.view',
            'teams.view',
            'reports.view',
        ]);
    }
}
