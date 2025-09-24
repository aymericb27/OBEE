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
            'code' => 'EC101',
            'name' => 'Algorithmique et structures de données',
            'description' => 'Bases des algorithmes et structures',
        ]);

        DB::table('element_constitutif')->insert([
            'code' => 'EC102',
            'name' => 'Introduction à la programmation',
            'description' => 'Initiation à Python et Java',
        ]);

        // EC pour UE102
        DB::table('element_constitutif')->insert([
            'code' => 'EC201',
            'name' => 'Analyse mathématique',
            'description' => 'Fonctions, limites, dérivées',
        ]);

        DB::table('element_constitutif')->insert([
            'code' => 'EC202',
            'name' => 'Algèbre linéaire',
            'description' => 'Matrices, vecteurs et systèmes linéaires',
        ]);

        // EC pour UE103
        DB::table('element_constitutif')->insert([
            'code' => 'EC301',
            'name' => 'Expression écrite et orale',
            'description' => 'Communication écrite et orale',
        ]);

        DB::table('element_constitutif')->insert([
            'code' => 'EC302',
            'name' => 'Anglais niveau 1',
            'description' => 'Cours d’anglais général',
        ]);
    }
}