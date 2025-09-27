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
            'name' => 'Comprendre les bases de l’algorithmique et des structures de données',
            'fk_AAT' => 1,
            'code' => 'AAV101',
        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Se familiariser avec la programmation procédurale et orientée objet',
            'fk_AAT' => 1,
            'code' => 'AAV102',


        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Développer une première autonomie dans la résolution de problèmes informatiques',
            'fk_AAT' => 1,
            'code' => 'AAV103',

        ]);

        // UE102 : Mathématiques et logique
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Acquérir les connaissances de base en analyse et algèbre pour les sciences informatiques',
            'fk_AAT' => 1,
            'code' => 'AAV104',

        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Développer la rigueur du raisonnement logique et mathématique',
            'fk_AAT' => 1,
            'code' => 'AAV105',

        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Comprendre l’importance des mathématiques pour la modélisation de problèmes',
            'fk_AAT' => 1,
            'code' => 'AAV106',

        ]);

        // UE103 : Communication et méthodologie
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Développer des compétences de communication écrite et orale',
            'fk_AAT' => 2,
            'code' => 'AAV107',

        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'S’initier à la méthodologie de travail universitaire',
            'fk_AAT' => 2,
            'code' => 'AAV108',

        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Améliorer ses capacités de communication en anglais',
            'fk_AAT' => 2,
            'code' => 'AAV109',

        ]);
    }
}
