<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcquisApprentissageViseSeeder extends Seeder
{
    public function run(): void
    {
        // UE101 : Fondamentaux en informatique
        DB::table('acquis_apprentissage_vise')->insert([
            'unite_enseignement_id' => 1,
            'description' => 'Comprendre les bases de l’algorithmique et des structures de données',
        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'unite_enseignement_id' => 1,
            'description' => 'Se familiariser avec la programmation procédurale et orientée objet',
        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'unite_enseignement_id' => 1,
            'description' => 'Développer une première autonomie dans la résolution de problèmes informatiques',
        ]);

        // UE102 : Mathématiques et logique
        DB::table('acquis_apprentissage_vise')->insert([
            'unite_enseignement_id' => 2,
            'description' => 'Acquérir les connaissances de base en analyse et algèbre pour les sciences informatiques',
        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'unite_enseignement_id' => 2,
            'description' => 'Développer la rigueur du raisonnement logique et mathématique',
        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'unite_enseignement_id' => 2,
            'description' => 'Comprendre l’importance des mathématiques pour la modélisation de problèmes',
        ]);

        // UE103 : Communication et méthodologie
        DB::table('acquis_apprentissage_vise')->insert([
            'unite_enseignement_id' => 3,
            'description' => 'Développer des compétences de communication écrite et orale',
        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'unite_enseignement_id' => 3,
            'description' => 'S’initier à la méthodologie de travail universitaire',
        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'unite_enseignement_id' => 3,
            'description' => 'Améliorer ses capacités de communication en anglais',
        ]);
    }
}