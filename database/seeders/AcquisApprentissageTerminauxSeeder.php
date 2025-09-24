<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcquisApprentissageTerminauxSeeder extends Seeder
{
    public function run(): void
    {

        /**
         * ACQUIS D'APPRENTISSAGE TERMINAUX / FINAUX (AAF)
         */

        // UE101 : Fondamentaux en informatique
        DB::table('acquis_apprentissage_terminaux')->insert([
            'code' => 'AAT101',
            'description' => 'Comprendre la logique',
        ]);

        DB::table('acquis_apprentissage_terminaux')->insert([
            'code' => 'AAT102',
            'description' => 'Savoir être bilingue',
        ]);
    }
}
