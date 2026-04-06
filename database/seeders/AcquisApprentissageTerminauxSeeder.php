<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AcquisApprentissageTerminauxSeeder extends Seeder
{
    public function run(): void
    {
        $programmeByCode = DB::table('programme')
            ->where('university_id', 1)
            ->pluck('id', 'code');

        $programmeId = $programmeByCode['PRO101'] ?? null;
        if ($programmeId === null) {
            throw new RuntimeException('Programme PRO101 introuvable pour initialiser les AAT.');
        }

        DB::table('acquis_apprentissage_terminaux')->insert([
            'code' => 'AAT101',
            'name' => 'Comprendre la logique',
            'description' => "L'etudiant demontre une comprehension claire de la logique en tant qu'outil de raisonnement et de structuration de la pensee. Il est capable d'identifier, analyser et formuler des arguments de maniere coherente, d'appliquer les principes de la logique formelle (connecteurs, propositions, deductions) et de reconnaitre les erreurs de raisonnement.",
            'university_id' => 1,
            'fk_programme' => (int) $programmeId,
        ]);

        DB::table('acquis_apprentissage_terminaux')->insert([
            'code' => 'AAT102',
            'name' => 'Savoir etre bilingue',
            'description' => "L'etudiant est capable de communiquer couramment dans deux langues, a l'oral comme a l'ecrit, en adaptant son registre de langue au contexte academique, professionnel ou social.",
            'university_id' => 1,
            'fk_programme' => (int) $programmeId,
        ]);

        DB::table('acquis_apprentissage_terminaux')->insert([
            'code' => 'AAT103',
            'name' => "Connaitre l'informatique",
            'description' => "A l'issue de la formation, l'etudiant est capable de definir les concepts fondamentaux de l'informatique, de decrire les principales composantes materielles et logicielles d'un systeme informatique.",
            'university_id' => 1,
            'fk_programme' => (int) $programmeId,
        ]);
    }
}

