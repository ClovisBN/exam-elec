<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ElectionStatusSeeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            ElectionStatusSeeder::class,
            RoleSeeder::class,
        ]);
    }
}
