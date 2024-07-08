<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ElectionStatus;

class ElectionStatusSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            ['status' => 'en attente'],
            ['status' => 'en cours'],
            ['status' => 'terminé']
        ];

        foreach ($statuses as $status) {
            ElectionStatus::create($status);
        }
    }
}
