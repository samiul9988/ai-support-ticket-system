<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => RoleEnum::ADMIN->value,
                'description' => 'Full access to all features',
            ],
            [
                'name' => 'Support Agent',
                'slug' => RoleEnum::AGENT->value,
                'description' => 'Can manage and reply to assigned tickets',
            ],
            [
                'name' => 'Customer',
                'slug' => RoleEnum::CUSTOMER->value,
                'description' => 'Can create and view their own tickets',
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $role['slug']],
                array_merge($role, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
