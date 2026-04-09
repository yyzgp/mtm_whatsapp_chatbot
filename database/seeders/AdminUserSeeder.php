<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@mtmcrm.com'],
            [
                'name' => 'System Admin',
                'password' => bcrypt('Admin@12345'),
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        $manager = User::updateOrCreate(
            ['email' => 'manager@mtmcrm.com'],
            [
                'name' => 'Sales Manager',
                'password' => bcrypt('Manager@12345'),
                'phone' => '+60123456789',
                'is_active' => true,
            ]
        );
        $manager->assignRole('sales_manager');

        $agent = User::updateOrCreate(
            ['email' => 'agent@mtmcrm.com'],
            [
                'name' => 'Sales Agent',
                'password' => bcrypt('Agent@12345'),
                'phone' => '+60198765432',
                'is_active' => true,
            ]
        );
        $agent->assignRole('sales_agent');
    }
}
