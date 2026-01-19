<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UEProgrammeSeeder extends Seeder
{
    public function run(): void
    {
        // Programme IDs :
        $informatique = 1;
        $mathematique = 2;
        $langue = 3;

        // UE IDs (en supposant qu’elles ont été insérées dans l’ordre)
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
            ['fk_unite_enseignement' => $ue101, 'fk_programme' => $informatique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue101, 'fk_programme' => $mathematique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue102, 'fk_programme' => $informatique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue102, 'fk_programme' => $mathematique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue103, 'fk_programme' => $informatique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue103, 'fk_programme' => $mathematique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue103, 'fk_programme' => $langue, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue104, 'fk_programme' => $informatique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue105, 'fk_programme' => $informatique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue105, 'fk_programme' => $mathematique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue106, 'fk_programme' => $informatique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue107, 'fk_programme' => $informatique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue108, 'fk_programme' => $informatique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue109, 'fk_programme' => $informatique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue109, 'fk_programme' => $mathematique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue110, 'fk_programme' => $informatique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue110, 'fk_programme' => $mathematique, "semester" => 1, 'university_id' => 1],
            ['fk_unite_enseignement' => $ue110, 'fk_programme' => $langue, "semester" => 1, 'university_id' => 1],
        ]);
    }
}
