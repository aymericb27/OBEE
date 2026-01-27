<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProSemesterSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pro_semester')->insert([

            // 🔵 Licence Informatique (PRO101) – 6 semestres / 180 ECTS
            ['ects' => 30, 'fk_programme' => 1, 'semester' => 1, 'university_id' => 1],
            ['ects' => 30, 'fk_programme' => 1, 'semester' => 2, 'university_id' => 1],
            ['ects' => 30, 'fk_programme' => 1, 'semester' => 3, 'university_id' => 1],
            ['ects' => 30, 'fk_programme' => 1, 'semester' => 4, 'university_id' => 1],
            ['ects' => 30, 'fk_programme' => 1, 'semester' => 5, 'university_id' => 1],
            ['ects' => 30, 'fk_programme' => 1, 'semester' => 6, 'university_id' => 1],

            // 🟢 Licence Mathématique (PRO102) – 10 semestres / 180 ECTS
            ['ects' => 18, 'fk_programme' => 2, 'semester' => 1, 'university_id' => 1],
            ['ects' => 18, 'fk_programme' => 2, 'semester' => 2, 'university_id' => 1],
            ['ects' => 18, 'fk_programme' => 2, 'semester' => 3, 'university_id' => 1],
            ['ects' => 18, 'fk_programme' => 2, 'semester' => 4, 'university_id' => 1],
            ['ects' => 18, 'fk_programme' => 2, 'semester' => 5, 'university_id' => 1],
            ['ects' => 18, 'fk_programme' => 2, 'semester' => 6, 'university_id' => 1],
            ['ects' => 18, 'fk_programme' => 2, 'semester' => 7, 'university_id' => 1],
            ['ects' => 18, 'fk_programme' => 2, 'semester' => 8, 'university_id' => 1],
            ['ects' => 18, 'fk_programme' => 2, 'semester' => 9, 'university_id' => 1],
            ['ects' => 18, 'fk_programme' => 2, 'semester' => 10, 'university_id' => 1],

            // 🟣 Licence Langue (PRO103) – 6 semestres / 120 ECTS
            ['ects' => 20, 'fk_programme' => 3, 'semester' => 1, 'university_id' => 1],
            ['ects' => 20, 'fk_programme' => 3, 'semester' => 2, 'university_id' => 1],
            ['ects' => 20, 'fk_programme' => 3, 'semester' => 3, 'university_id' => 1],
            ['ects' => 20, 'fk_programme' => 3, 'semester' => 4, 'university_id' => 1],
            ['ects' => 20, 'fk_programme' => 3, 'semester' => 5, 'university_id' => 1],
            ['ects' => 20, 'fk_programme' => 3, 'semester' => 6, 'university_id' => 1],

        ]);
    }
}
