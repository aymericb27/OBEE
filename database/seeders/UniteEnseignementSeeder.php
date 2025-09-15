<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UniteEnseignementSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('unite_enseignement')->insert([
            'code' => 'UE101',
            'nom' => 'Fondamentaux en informatique',
            'description' => 'Cette UE vise à fournir les bases essentielles de l’informatique. Elle aborde l’algorithmique, les structures de données fondamentales, et les concepts de programmation. L’étudiant apprend à analyser des problèmes, concevoir des solutions efficaces et coder des programmes simples en Python et Java. Cette UE prépare aux cours avancés de développement logiciel et aux projets tutorés.',
            'ects' => 6,
        ]);

        DB::table('unite_enseignement')->insert([
            'code' => 'UE102',
            'nom' => 'Mathématiques et logique',
            'description' => 'Cette UE développe les compétences en mathématiques appliquées et raisonnement logique, indispensables en informatique et sciences exactes. Elle couvre l’analyse (fonctions, dérivées, intégrales), l’algèbre linéaire (matrices, vecteurs, systèmes linéaires) et la logique formelle. Les étudiants acquièrent des méthodes pour formaliser des problèmes, raisonner rigoureusement et préparer des modèles mathématiques pour la programmation et l’intelligence artificielle.',
            'ects' => 5,
        ]);

        DB::table('unite_enseignement')->insert([
            'code' => 'UE103',
            'nom' => 'Communication et méthodologie',
            'description' => 'Cette UE développe les compétences transversales nécessaires à la réussite universitaire et professionnelle. Elle inclut l’expression écrite et orale, la méthodologie de travail (recherche documentaire, gestion de projet, travail en équipe) et l’anglais appliqué au domaine scientifique et technique. L’objectif est de permettre aux étudiants de communiquer efficacement, de structurer leur travail et de s’adapter à un environnement international.',
            'ects' => 4,
        ]);
    }
}