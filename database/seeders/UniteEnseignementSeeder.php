<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UniteEnseignementSeeder extends Seeder
{
    public function run(): void
    {
        // --- SEMESTRE 1 : Automne 2025 ---
        DB::table('unite_enseignement')->insert([
            'code' => 'UE101',
            'name' => 'Fondamentaux en informatique',
            'description' => 'Cette UE vise à fournir les bases essentielles de l’informatique. Elle aborde l’algorithmique, les structures de données fondamentales, et les concepts de programmation. L’étudiant apprend à analyser des problèmes, concevoir des solutions efficaces et coder des programmes simples en Python et Java. Cette UE prépare aux cours avancés de développement logiciel et aux projets tutorés.',
            'ects' => 6,
        ]);

        DB::table('unite_enseignement')->insert([
            'code' => 'UE102',
            'name' => 'Mathématiques et logique',
            'description' => 'Cette UE développe les compétences en mathématiques appliquées et raisonnement logique, indispensables en informatique et sciences exactes. Elle couvre l’analyse (fonctions, dérivées, intégrales), l’algèbre linéaire (matrices, vecteurs, systèmes linéaires) et la logique formelle. Les étudiants acquièrent des méthodes pour formaliser des problèmes, raisonner rigoureusement et préparer des modèles mathématiques pour la programmation et l’intelligence artificielle.',
            'ects' => 5,

        ]);

        DB::table('unite_enseignement')->insert([
            'code' => 'UE103',
            'name' => 'Communication et méthodologie',
            'description' => 'Cette UE développe les compétences transversales nécessaires à la réussite universitaire et professionnelle. Elle inclut l’expression écrite et orale, la méthodologie de travail (recherche documentaire, gestion de projet, travail en équipe) et l’anglais appliqué au domaine scientifique et technique. L’objectif est de permettre aux étudiants de communiquer efficacement, de structurer leur travail et de s’adapter à un environnement international.',
            'ects' => 4,
        ]);

        DB::table('unite_enseignement')->insert([
            'code' => 'UE104',
            'name' => 'Architecture des ordinateurs',
            'description' => 'Cette UE présente les principes fondamentaux de l’architecture des ordinateurs. Elle couvre la représentation de l’information, les circuits logiques, le fonctionnement des processeurs, la mémoire et les périphériques. L’étudiant apprend à comprendre la structure interne d’un ordinateur, à relier le matériel et le logiciel, et à évaluer les performances des systèmes informatiques.',
            'ects' => 5,
        ]);

        DB::table('unite_enseignement')->insert([
            'code' => 'UE105',
            'name' => 'Bases de données relationnelles',
            'description' => 'Cette UE initie aux concepts et outils des bases de données. Elle aborde le modèle relationnel, le langage SQL, la conception de schémas (modèle entité-association) et les principes de normalisation. Les étudiants apprennent à concevoir, interroger et administrer des bases de données à l’aide de systèmes tels que MySQL ou PostgreSQL.',
            'ects' => 6,

        ]);

        // --- SEMESTRE 2 : Printemps 2026 ---
        DB::table('unite_enseignement')->insert([
            'code' => 'UE106',
            'name' => 'Développement web',
            'description' => 'Cette UE introduit les bases du développement web côté client et serveur. Les étudiants apprennent HTML, CSS, JavaScript et les principes du développement web dynamique avec PHP et frameworks modernes. L’accent est mis sur la conception d’interfaces interactives et la mise en ligne de projets complets.',
            'ects' => 5,

        ]);

        DB::table('unite_enseignement')->insert([
            'code' => 'UE107',
            'name' => 'Systèmes d’exploitation',
            'description' => 'Cette UE aborde les concepts essentiels des systèmes d’exploitation : gestion des processus, de la mémoire, du stockage et des fichiers. Les étudiants découvrent le fonctionnement interne d’OS comme Linux et Windows, et apprennent à utiliser la ligne de commande pour l’administration et l’automatisation de tâches.',
            'ects' => 5,
        ]);

        DB::table('unite_enseignement')->insert([
            'code' => 'UE108',
            'name' => 'Réseaux informatiques',
            'description' => 'Cette UE introduit les réseaux informatiques et l’architecture Internet. Les thèmes incluent les modèles OSI et TCP/IP, le routage, les protocoles (IP, TCP, HTTP, DNS) et la configuration de réseaux locaux. Les étudiants apprennent à analyser le trafic réseau et à comprendre le fonctionnement des communications numériques.',
            'ects' => 5,
        ]);

        DB::table('unite_enseignement')->insert([
            'code' => 'UE109',
            'name' => 'Algorithmique avancée',
            'description' => 'Cette UE approfondit les connaissances en algorithmique. Les étudiants étudient la complexité, les structures de données avancées (arbres, graphes, tas) et les algorithmes de tri, recherche et parcours. L’objectif est de maîtriser la conception et l’analyse d’algorithmes performants pour résoudre des problèmes complexes.',
            'ects' => 6,
        ]);

        DB::table('unite_enseignement')->insert([
            'code' => 'UE110',
            'name' => 'Projet tutoré d’intégration',
            'description' => 'Cette UE met en pratique l’ensemble des compétences acquises dans les autres enseignements. Les étudiants réalisent un projet complet, en équipe, mêlant conception, développement, gestion de base de données et communication. Ce travail favorise la créativité, l’autonomie et la collaboration en conditions quasi-professionnelles.',
            'ects' => 8,
        ]);
    }
}
