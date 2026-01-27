<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class programmeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('programme')->insert([
            'name' => 'license en informatique',
            'code' => 'PRO101',
            'ects' => 180,
            'university_id' => 1
        ]);
        DB::table('programme')->insert([
            'name' => 'license en mathématique',
            'code' => 'PRO102',
            'ects' => 180,
            'university_id' => 1
        ]);
        DB::table('programme')->insert([
            'name' => 'license en langue',
            'code' => 'PRO103',
            'ects' => 120,
            'university_id' => 1

        ]);
    }
}
