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
            'description' => 'L’étudiant maîtrise les principes fondamentaux de l’algorithmique et des structures de données. Il est capable de concevoir des solutions logiques à des problèmes simples, de traduire ces solutions en algorithmes clairs et efficaces, et de sélectionner les structures de données appropriées (listes, tableaux, piles, files, arbres, etc.) en fonction du contexte. Cet acquis constitue une base essentielle pour le développement de logiciels, l’optimisation de programmes et la poursuite d’études avancées en informatique.',
            'fk_AAT' => 1,
            'code' => 'AAV101',
        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Se familiariser avec la programmation procédurale et orientée objet',
            'description' => 'description',
            'fk_AAT' => 1,
            'code' => 'AAV102',


        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Développer une première autonomie dans la résolution de problèmes informatiques',
            'description' => 'description',
            'fk_AAT' => 1,
            'code' => 'AAV103',

        ]);

        // UE102 : Mathématiques et logique
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Acquérir les connaissances de base en analyse et algèbre pour les sciences informatiques',
            'description' => 'description',
            'fk_AAT' => 1,
            'code' => 'AAV104',

        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Développer la rigueur du raisonnement logique et mathématique',
            'description' => 'description',
            'fk_AAT' => 1,
            'code' => 'AAV105',

        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Comprendre l’importance des mathématiques pour la modélisation de problèmes',
            'description' => 'description',
            'fk_AAT' => 1,
            'code' => 'AAV106',

        ]);

        // UE103 : Communication et méthodologie
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Développer des compétences de communication écrite et orale',
            'description' => 'description',
            'fk_AAT' => 2,
            'code' => 'AAV107',

        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'S’initier à la méthodologie de travail universitaire',
            'description' => 'description',
            'fk_AAT' => 2,
            'code' => 'AAV108',

        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Améliorer ses capacités de communication en anglais',
            'description' => 'description',
            'fk_AAT' => 2,
            'code' => 'AAV109',

        ]);
    }
}
