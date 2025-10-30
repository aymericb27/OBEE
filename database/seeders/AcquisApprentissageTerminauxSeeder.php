<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcquisApprentissageTerminauxSeeder extends Seeder
{
    public function run(): void
    {

        /**
         * ACQUIS D'APPRENTISSAGE TERMINAUX / FINAUX (AAF)
         */

        // UE101 : Fondamentaux en informatique
        DB::table('acquis_apprentissage_terminaux')->insert([
            'code' => 'AAT101',
            'name' => 'Comprendre la logique',
            'description' => 'L’étudiant démontre une compréhension claire de la logique en tant qu’outil de raisonnement et de structuration de la pensée. Il est capable d’identifier, analyser et formuler des arguments de manière cohérente, d’appliquer les principes de la logique formelle (connecteurs, propositions, déductions) et de reconnaître les erreurs de raisonnement. Cet acquis permet de développer la rigueur intellectuelle nécessaire à la résolution de problèmes complexes, tant dans le domaine académique que professionnel.',
        ]);

        DB::table('acquis_apprentissage_terminaux')->insert([
            'code' => 'AAT102',
            'name' => 'Savoir être bilingue',
            'description' => 'L’étudiant est capable de communiquer couramment dans deux langues, à l’oral comme à l’écrit, en adaptant son registre de langue au contexte académique, professionnel ou social. Il démontre une aisance dans la compréhension et l’expression, tant pour des interactions quotidiennes que pour des contenus spécialisés. Cet acquis favorise l’ouverture culturelle, le développement de compétences interculturelles et une meilleure insertion dans un environnement international.',
        ]);

        DB::table('acquis_apprentissage_terminaux')->insert([
            'code' => 'AAT103',
            'name' => "Connaitre l'informatique",
            'description' => 'À l’issue de la formation, l’étudiant est capable de définir les concepts fondamentaux de l’informatique, de décrire les principales composantes matérielles et logicielles d’un système informatique, et de situer le rôle de l’informatique dans la société et les autres disciplines.',
        ]);
    }
}
