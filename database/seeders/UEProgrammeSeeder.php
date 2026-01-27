<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UEProgrammeSeeder extends Seeder
{
    public function run(): void
    {
        // Programme IDs
        $informatique = 1;
        $mathematique = 2;
        $langue = 3;

        // pro_semester IDs (SEMESTRE 1)
        $sem1_info = 1;   // programme 1, semester 1
        $sem1_math = 7;   // programme 2, semester 1
        $sem1_lang = 17;  // programme 3, semester 1

        // UE IDs
        $ue101 = 1;
        $ue102 = 2;
        $ue103 = 3;
        $ue104 = 4;
        $ue105 = 5;
        $ue106 = 6;
        $ue107 = 7;
        $ue108 = 8;
        $ue109 = 9;
        $ue110 = 10;

        DB::table('ue_programme')->insert([

            ['fk_unite_enseignement' => $ue101, 'fk_programme' => $informatique, 'fk_semester' => $sem1_info, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue101, 'fk_programme' => $mathematique, 'fk_semester' => $sem1_math, 'university_id' => 1],

            ['fk_unite_enseignement' => $ue102, 'fk_programme' => $informatique, 'fk_semester' => $sem1_info, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue102, 'fk_programme' => $mathematique, 'fk_semester' => $sem1_math, 'university_id' => 1],

            ['fk_unite_enseignement' => $ue103, 'fk_programme' => $informatique, 'fk_semester' => $sem1_info, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue103, 'fk_programme' => $mathematique, 'fk_semester' => $sem1_math, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue103, 'fk_programme' => $langue, 'fk_semester' => $sem1_lang, 'university_id' => 1],

            ['fk_unite_enseignement' => $ue104, 'fk_programme' => $informatique, 'fk_semester' => $sem1_info, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue105, 'fk_programme' => $informatique, 'fk_semester' => $sem1_info, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue105, 'fk_programme' => $mathematique, 'fk_semester' => $sem1_math, 'university_id' => 1],

            ['fk_unite_enseignement' => $ue106, 'fk_programme' => $informatique, 'fk_semester' => $sem1_info, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue107, 'fk_programme' => $informatique, 'fk_semester' => $sem1_info, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue108, 'fk_programme' => $informatique, 'fk_semester' => $sem1_info, 'university_id' => 1],

            ['fk_unite_enseignement' => $ue109, 'fk_programme' => $informatique, 'fk_semester' => $sem1_info, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue109, 'fk_programme' => $mathematique, 'fk_semester' => $sem1_math, 'university_id' => 1],

            ['fk_unite_enseignement' => $ue110, 'fk_programme' => $informatique, 'fk_semester' => $sem1_info, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue110, 'fk_programme' => $mathematique, 'fk_semester' => $sem1_math, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue110, 'fk_programme' => $langue, 'fk_semester' => $sem1_lang, 'university_id' => 1],
        ]);
    }
}
