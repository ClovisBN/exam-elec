<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['name' => 'admin'],
            ['name' => 'user'],
            ['name' => 'délégué'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
