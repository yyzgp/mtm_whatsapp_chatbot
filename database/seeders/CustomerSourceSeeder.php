<?php

namespace Database\Seeders;

use App\Models\CustomerSource;
use Illuminate\Database\Seeder;

class CustomerSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['name' => 'WhatsApp', 'color' => '#25D366', 'icon' => 'whatsapp'],
            ['name' => 'Website', 'color' => '#3B82F6', 'icon' => 'globe'],
            ['name' => 'Referral', 'color' => '#8B5CF6', 'icon' => 'users'],
            ['name' => 'Cold Call', 'color' => '#F59E0B', 'icon' => 'phone'],
            ['name' => 'Email', 'color' => '#6366F1', 'icon' => 'mail'],
            ['name' => 'Social Media', 'color' => '#EC4899', 'icon' => 'share'],
            ['name' => 'Event', 'color' => '#14B8A6', 'icon' => 'calendar'],
            ['name' => 'Other', 'color' => '#6B7280', 'icon' => 'dots'],
        ];

        foreach ($sources as $source) {
            CustomerSource::updateOrCreate(['name' => $source['name']], $source);
        }
    }
}
