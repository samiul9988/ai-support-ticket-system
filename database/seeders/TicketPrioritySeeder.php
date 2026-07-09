<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketPrioritySeeder extends Seeder
{
    public function run(): void
    {
        $priorities = [
            [
                'name' => 'Low',
                'slug' => 'low',
                'description' => 'Non-urgent issues, can be addressed when time permits',
                'color' => '#6B7280',
                'sla_hours' => 72,
                'is_active' => true,
            ],
            [
                'name' => 'Medium',
                'slug' => 'medium',
                'description' => 'Standard priority for general inquiries',
                'color' => '#3B82F6',
                'sla_hours' => 48,
                'is_active' => true,
            ],
            [
                'name' => 'High',
                'slug' => 'high',
                'description' => 'Important issues requiring prompt attention',
                'color' => '#F59E0B',
                'sla_hours' => 24,
                'is_active' => true,
            ],
            [
                'name' => 'Urgent',
                'slug' => 'urgent',
                'description' => 'Critical issues requiring immediate attention',
                'color' => '#EF4444',
                'sla_hours' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($priorities as $priority) {
            DB::table('ticket_priorities')->updateOrInsert(
                ['slug' => $priority['slug']],
                array_merge($priority, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
