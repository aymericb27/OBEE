<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AAVAATSeeder extends Seeder
{
    public function run(): void
    {
        /*
         |--------------------------------------------------------------------------
         | Récupération des IDs
         |--------------------------------------------------------------------------
         */
        $aav = DB::table('acquis_apprentissage_vise')
            ->pluck('id', 'code'); // ['AAV101' => 1, ...]

        $aat = DB::table('acquis_apprentissage_terminaux')
            ->pluck('id', 'code'); // ['AAT101' => 1, ...]

        /*
         |--------------------------------------------------------------------------
         | Liaisons AAV ↔ AAT avec contribution (1 à 3)
         |--------------------------------------------------------------------------
         */
        $relations = [
            // Logique & algorithmique → Comprendre la logique
            ['aav' => 'AAV101', 'aat' => 'AAT101', 'contribution' => 3,"university_id" => 1],
            ['aav' => 'AAV103', 'aat' => 'AAT101', 'contribution' => 2,"university_id" => 1],
            ['aav' => 'AAV105', 'aat' => 'AAT101', 'contribution' => 3,"university_id" => 1],

            // Anglais & communication → Être bilingue
            ['aav' => 'AAV107', 'aat' => 'AAT102', 'contribution' => 2,"university_id" => 1],
            ['aav' => 'AAV109', 'aat' => 'AAT102', 'contribution' => 3,"university_id" => 1],

            // Informatique générale → Connaitre l’informatique
            ['aav' => 'AAV101', 'aat' => 'AAT103', 'contribution' => 2,"university_id" => 1],
            ['aav' => 'AAV102', 'aat' => 'AAT103', 'contribution' => 3,"university_id" => 1],
            ['aav' => 'AAV110', 'aat' => 'AAT103', 'contribution' => 3,"university_id" => 1],
            ['aav' => 'AAV112', 'aat' => 'AAT103', 'contribution' => 3,"university_id" => 1],
            ['aav' => 'AAV114', 'aat' => 'AAT103', 'contribution' => 2,"university_id" => 1],
            ['aav' => 'AAV116', 'aat' => 'AAT103', 'contribution' => 2,"university_id" => 1],
            ['aav' => 'AAV118', 'aat' => 'AAT103', 'contribution' => 2,"university_id" => 1],
        ];

        /*
         |--------------------------------------------------------------------------
         | Insertion
         |--------------------------------------------------------------------------
         */
        foreach ($relations as $relation) {
            DB::table('aav_aat')->insert([
                'fk_aav' => $aav[$relation['aav']],
                'fk_aat' => $aat[$relation['aat']],
                'contribution' => $relation['contribution'],
                "university_id" => $relation['university_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
