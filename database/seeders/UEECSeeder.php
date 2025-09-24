<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UEECSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('ueec')->insert([
            'fk_unite_enseignement' => 1,
            'fk_element_constitutif' => 1,
        ]);

        DB::table('ueec')->insert([
            'fk_unite_enseignement' => 1,
            'fk_element_constitutif' => 2,
        ]);
        DB::table('ueec')->insert([
            'fk_unite_enseignement' => 2,
            'fk_element_constitutif' => 3,
        ]);
            DB::table('ueec')->insert([
            'fk_unite_enseignement' => 2,
            'fk_element_constitutif' => 4,
        ]);
            DB::table('ueec')->insert([
            'fk_unite_enseignement' => 3,
            'fk_element_constitutif' => 5,
        ]);
            DB::table('ueec')->insert([
            'fk_unite_enseignement' => 3,
            'fk_element_constitutif' => 6,
        ]);
    }
}
