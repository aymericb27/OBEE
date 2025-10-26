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

        // UE104 : Architecture des ordinateurs
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Comprendre la structure et le fonctionnement d’un ordinateur',
            'description' => 'L’étudiant identifie les principaux composants matériels (processeur, mémoire, bus, périphériques) et explique leur rôle dans le traitement de l’information. Il comprend les interactions entre matériel et logiciel, ainsi que les principes de base du fonctionnement interne d’un ordinateur.',
            'fk_AAT' => 3,
            'code' => 'AAV110',
        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Analyser les performances d’un système informatique',
            'description' => 'L’étudiant apprend à évaluer la rapidité et l’efficacité d’un ordinateur en fonction de son architecture, du jeu d’instructions et des caractéristiques du matériel. Il est capable d’interpréter des mesures de performance et d’en déduire des pistes d’optimisation.',
            'fk_AAT' => 3,
            'code' => 'AAV111',
        ]);

        // UE105 : Bases de données relationnelles
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Modéliser une base de données à l’aide du modèle entité-association',
            'description' => 'L’étudiant sait représenter les besoins d’un système d’information sous forme de schémas conceptuels, identifier les entités, relations et contraintes, et traduire cette modélisation dans un modèle relationnel.',
            'fk_AAT' => 4,
            'code' => 'AAV112',
        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Maîtriser le langage SQL pour la gestion de données',
            'description' => 'L’étudiant apprend à créer, interroger et manipuler des bases de données relationnelles en utilisant SQL. Il est capable d’écrire des requêtes complexes incluant jointures, sous-requêtes et agrégations.',
            'fk_AAT' => 4,
            'code' => 'AAV113',
        ]);

        // UE106 : Développement web
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Concevoir et développer une page web statique et dynamique',
            'description' => 'L’étudiant maîtrise les langages HTML, CSS et JavaScript pour construire des interfaces web interactives. Il comprend les notions de DOM, d’événements et de responsive design.',
            'fk_AAT' => 5,
            'code' => 'AAV114',
        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Comprendre les bases du développement web côté serveur',
            'description' => 'L’étudiant apprend à créer des applications web dynamiques en utilisant un langage serveur (comme PHP) et à gérer l’échange de données entre client et serveur via des formulaires ou des API.',
            'fk_AAT' => 5,
            'code' => 'AAV115',
        ]);

        // UE107 : Systèmes d’exploitation
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Identifier les principaux services d’un système d’exploitation',
            'description' => 'L’étudiant comprend les mécanismes de gestion des processus, de la mémoire, des fichiers et des périphériques. Il sait expliquer le rôle du noyau et les interactions entre les composants du système.',
            'fk_AAT' => 6,
            'code' => 'AAV116',
        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Utiliser efficacement un système Linux en ligne de commande',
            'description' => 'L’étudiant apprend les commandes fondamentales de Linux pour naviguer dans le système de fichiers, manipuler des données et automatiser des tâches via des scripts shell simples.',
            'fk_AAT' => 6,
            'code' => 'AAV117',
        ]);

        // UE108 : Réseaux informatiques
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Comprendre les principes de base des réseaux et de l’Internet',
            'description' => 'L’étudiant identifie les couches du modèle OSI et du modèle TCP/IP, comprend le fonctionnement des protocoles fondamentaux (IP, TCP, UDP, HTTP) et leur rôle dans la communication entre machines.',
            'fk_AAT' => 7,
            'code' => 'AAV118',
        ]);
        DB::table('acquis_apprentissage_vise')->insert([
            'name' => 'Configurer et diagnostiquer un réseau local simple',
            'description' => 'L’étudiant sait attribuer des adresses IP, configurer un routeur ou un commutateur, et utiliser des outils de diagnostic (ping, traceroute, Wireshark) pour analyser le trafic réseau.',
            'fk_AAT' => 7,
            'code' => 'AAV119',
        ]);
    }
}
