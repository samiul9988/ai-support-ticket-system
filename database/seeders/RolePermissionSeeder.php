<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = DB::table('permissions')->get()->keyBy('slug');

        $rolePermissions = [
            'admin' => $permissions->keys()->toArray(),

            'agent' => [
                'ticket.view-assigned',
                'ticket.view-all',
                'ticket.create',
                'ticket.update',
                'ticket.reply',
                'ticket.change-status',
                'categories.view',
                'knowledge.view',
                'ai.view',
            ],

            'customer' => [
                'ticket.view-own',
                'ticket.create',
                'ticket.reply',
                'categories.view',
                'knowledge.view',
            ],
        ];

        $roles = DB::table('roles')->get()->keyBy('slug');

        foreach ($rolePermissions as $roleSlug => $permSlugs) {
            $roleId = $roles[$roleSlug]->id ?? null;
            if (! $roleId) continue;

            foreach ($permSlugs as $permSlug) {
                $permId = $permissions[$permSlug]->id ?? null;
                if (! $permId) continue;

                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permId,
                ]);
            }
        }
    }
}
