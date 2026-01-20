<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AAVUESeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        //visée 

        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 1,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 1,
        ]);

        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 1,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 2,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 1,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 3,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 2,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 4,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 2,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 5,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 2,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 6,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 3,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 7,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 3,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 8,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 3,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 9,
        ]);
        // UE104 : Architecture des ordinateurs
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 4,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 10,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 4,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 11,
        ]);

        // UE105 : Bases de données relationnelles
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 5,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 12,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 5,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 13,
        ]);

        // UE106 : Développement web
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 6,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 14,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 6,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 15,
        ]);

        // UE107 : Systèmes d’exploitation
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 7,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 16,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 7,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 17,
        ]);

        // UE108 : Réseaux informatiques
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 8,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 18,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 8,
            "university_id" => 1,
            'fk_acquis_apprentissage_vise' => 19,
        ]);


        //prérequis
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 1,
            "university_id" => 1,
            'fk_acquis_apprentissage_prerequis' => 4,
        ]);
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 1,
            "university_id" => 1,
            'fk_acquis_apprentissage_prerequis' => 5,
        ]);
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 1,
            "university_id" => 1,
            'fk_acquis_apprentissage_prerequis' => 6,
        ]);
        // UE104 (Architecture des ordinateurs) dépend de notions de base en informatique et logique
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 4,
            "university_id" => 1,
            'fk_acquis_apprentissage_prerequis' => 1, // bases algorithmique
        ]);
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 4,
            "university_id" => 1,
            'fk_acquis_apprentissage_prerequis' => 5, // rigueur logique
        ]);

        // UE105 (Bases de données) dépend de la logique et de la programmation
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 5,
            "university_id" => 1,
            'fk_acquis_apprentissage_prerequis' => 2, // programmation
        ]);
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 5,
            "university_id" => 1,
            'fk_acquis_apprentissage_prerequis' => 5, // logique
        ]);

        // UE106 (Développement web) dépend des bases en programmation et communication
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 6,
            "university_id" => 1,
            'fk_acquis_apprentissage_prerequis' => 2, // programmation orientée objet
        ]);
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 6,
            "university_id" => 1,
            'fk_acquis_apprentissage_prerequis' => 7, // communication écrite et orale
        ]);

        // UE107 (Systèmes d’exploitation) dépend de l’architecture et des fondamentaux en info
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 7,
            "university_id" => 1,
            'fk_acquis_apprentissage_prerequis' => 1, // algorithmique
        ]);
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 7,
            "university_id" => 1,
            'fk_acquis_apprentissage_prerequis' => 10, // structure ordinateur
        ]);

        // UE108 (Réseaux informatiques) dépend de l’architecture et des systèmes d’exploitation
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 8,
            "university_id" => 1,
            'fk_acquis_apprentissage_prerequis' => 10, // architecture
        ]);
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 8,
            "university_id" => 1,
            'fk_acquis_apprentissage_prerequis' => 16, // services système d’exploitation
        ]);
    }
}
