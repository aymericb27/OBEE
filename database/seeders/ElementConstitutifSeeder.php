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
            'description' => "Ce cours initie l’étudiant aux principes fondamentaux de la programmation et de l’algorithmique. Il aborde la logique de résolution de problèmes, la conception d’algorithmes et leur traduction dans un langage de programmation moderne (par exemple Python). Les notions couvertes incluent les variables, les types de données, les structures de contrôle (conditions et boucles), les fonctions, ainsi que les structures de données de base. Le cours met également l’accent sur la pratique à travers des exercices et mini-projets, afin de développer la capacité à écrire, comprendre et déboguer des programmes simples. À l’issue du cours, l’étudiant sera en mesure de concevoir des programmes autonomes répondant à des problèmes concrets et disposera des bases nécessaires pour poursuivre son apprentissage en informatique.",
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