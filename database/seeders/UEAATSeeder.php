<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UeAatSeeder extends Seeder
{
    public function run(): void
    {
        $pivot = [

            // UE101 : Fondamentaux en informatique
            ['fk_ue' => 1, 'fk_aat' => 1, 'contribution' => rand(1, 3), "university_id" => 1], // logique
            ['fk_ue' => 1, 'fk_aat' => 3, 'contribution' => rand(1, 3), "university_id" => 1], // informatique

            // UE102 : Mathématiques et logique
            ['fk_ue' => 2, 'fk_aat' => 1, 'contribution' => rand(1, 3), "university_id" => 1], // logique

            // UE103 : Communication et méthodologie
            ['fk_ue' => 3, 'fk_aat' => 2, 'contribution' => rand(1, 3), "university_id" => 1], // bilingue

            // UE104 : Architecture des ordinateurs
            ['fk_ue' => 4, 'fk_aat' => 3, 'contribution' => rand(1, 3), "university_id" => 1], // informatique

            // UE105 : Bases de données relationnelles
            ['fk_ue' => 5, 'fk_aat' => 3, 'contribution' => rand(1, 3), "university_id" => 1],

            // UE106 : Développement web
            ['fk_ue' => 6, 'fk_aat' => 3, 'contribution' => rand(1, 3), "university_id" => 1],

            // UE107 : Systèmes d’exploitation
            ['fk_ue' => 7, 'fk_aat' => 3, 'contribution' => rand(1, 3), "university_id" => 1],

            // UE108 : Réseaux informatiques
            ['fk_ue' => 8, 'fk_aat' => 3, 'contribution' => rand(1, 3), "university_id" => 1],

            // UE109 : Algorithmique avancée
            ['fk_ue' => 9, 'fk_aat' => 1, 'contribution' => rand(1, 3), "university_id" => 1], // logique
            ['fk_ue' => 9, 'fk_aat' => 3, 'contribution' => rand(1, 3), "university_id" => 1], // informatique

            // UE110 : Projet tutoré d’intégration
            ['fk_ue' => 10, 'fk_aat' => 2, 'contribution' => rand(1, 3), "university_id" => 1], // communication
            ['fk_ue' => 10, 'fk_aat' => 3, 'contribution' => rand(1, 3), "university_id" => 1], // informatique
        ];

        foreach ($pivot as $row) {
            DB::table('ue_aat')->insert([
                'fk_ue'        => $row['fk_ue'],
                'fk_aat'       => $row['fk_aat'],
                'contribution' => $row['contribution'],
                'university_id' => $row['university_id'],
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}
