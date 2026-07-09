<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'View All Tickets', 'slug' => 'ticket.view-all', 'description' => 'View all tickets in the system', 'group' => 'tickets'],
            ['name' => 'View Assigned Tickets', 'slug' => 'ticket.view-assigned', 'description' => 'View only assigned tickets', 'group' => 'tickets'],
            ['name' => 'View Own Tickets', 'slug' => 'ticket.view-own', 'description' => 'View own created tickets', 'group' => 'tickets'],
            ['name' => 'Create Ticket', 'slug' => 'ticket.create', 'description' => 'Create new support tickets', 'group' => 'tickets'],
            ['name' => 'Update Ticket', 'slug' => 'ticket.update', 'description' => 'Update ticket details', 'group' => 'tickets'],
            ['name' => 'Delete Ticket', 'slug' => 'ticket.delete', 'description' => 'Delete tickets permanently', 'group' => 'tickets'],
            ['name' => 'Assign Agent', 'slug' => 'ticket.assign', 'description' => 'Assign agents to tickets', 'group' => 'tickets'],
            ['name' => 'Change Status', 'slug' => 'ticket.change-status', 'description' => 'Change ticket status', 'group' => 'tickets'],
            ['name' => 'Reply to Ticket', 'slug' => 'ticket.reply', 'description' => 'Add replies to tickets', 'group' => 'tickets'],

            ['name' => 'Manage Users', 'slug' => 'users.manage', 'description' => 'Create, update, delete users', 'group' => 'users'],
            ['name' => 'View Users', 'slug' => 'users.view', 'description' => 'View user list and details', 'group' => 'users'],

            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'description' => 'Create and manage roles', 'group' => 'roles'],

            ['name' => 'Manage Categories', 'slug' => 'categories.manage', 'description' => 'Manage ticket categories', 'group' => 'categories'],
            ['name' => 'View Categories', 'slug' => 'categories.view', 'description' => 'View ticket categories', 'group' => 'categories'],

            ['name' => 'Manage Knowledge Base', 'slug' => 'knowledge.manage', 'description' => 'Create and manage knowledge articles', 'group' => 'knowledge_base'],
            ['name' => 'View Knowledge Base', 'slug' => 'knowledge.view', 'description' => 'View knowledge base articles', 'group' => 'knowledge_base'],

            ['name' => 'View AI Responses', 'slug' => 'ai.view', 'description' => 'View AI generated responses', 'group' => 'ai'],
            ['name' => 'Manage AI Settings', 'slug' => 'ai.manage', 'description' => 'Configure AI service settings', 'group' => 'ai'],
            ['name' => 'View AI Logs', 'slug' => 'ai.view-logs', 'description' => 'View AI usage and prompt logs', 'group' => 'ai'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                array_merge($permission, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
