<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ElementConstitutifSeeder extends Seeder
{
    public function run(): void
    {
        // EC pour UE101
        DB::table('element_constitutif')->insert([
            'fk_unite_enseignement' => 1,
            'code' => 'EC101',
            'nom' => 'Algorithmique et structures de données',
            'description' => 'Bases des algorithmes et structures',
            'volume_horaire' => 30,
        ]);

        DB::table('element_constitutif')->insert([
            'fk_unite_enseignement' => 1,
            'code' => 'EC102',
            'nom' => 'Introduction à la programmation',
            'description' => 'Initiation à Python et Java',
            'volume_horaire' => 40,
        ]);

        // EC pour UE102
        DB::table('element_constitutif')->insert([
            'fk_unite_enseignement' => 2,
            'code' => 'EC201',
            'nom' => 'Analyse mathématique',
            'description' => 'Fonctions, limites, dérivées',
            'volume_horaire' => 25,
        ]);

        DB::table('element_constitutif')->insert([
            'fk_unite_enseignement' => 2,
            'code' => 'EC202',
            'nom' => 'Algèbre linéaire',
            'description' => 'Matrices, vecteurs et systèmes linéaires',
            'volume_horaire' => 30,
        ]);

        // EC pour UE103
        DB::table('element_constitutif')->insert([
            'fk_unite_enseignement' => 3,
            'code' => 'EC301',
            'nom' => 'Expression écrite et orale',
            'description' => 'Communication écrite et orale',
            'volume_horaire' => 20,
        ]);

        DB::table('element_constitutif')->insert([
            'fk_unite_enseignement' => 3,
            'code' => 'EC302',
            'nom' => 'Anglais niveau 1',
            'description' => 'Cours d’anglais général',
            'volume_horaire' => 15,
        ]);
    }
}