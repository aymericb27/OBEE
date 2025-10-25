<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AAVUESeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        //visée 
        
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 1,
            'fk_acquis_apprentissage_vise' => 1,
        ]);

        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 1,
            'fk_acquis_apprentissage_vise' => 2,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 1,
            'fk_acquis_apprentissage_vise' => 3,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 2,
            'fk_acquis_apprentissage_vise' => 4,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 2,
            'fk_acquis_apprentissage_vise' => 5,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 2,
            'fk_acquis_apprentissage_vise' => 6,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 3,
            'fk_acquis_apprentissage_vise' => 7,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 3,
            'fk_acquis_apprentissage_vise' => 8,
        ]);
        DB::table('aavue_vise')->insert([
            'fk_unite_enseignement' => 3,
            'fk_acquis_apprentissage_vise' => 9,
        ]);

        //prérequis
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 1,
            'fk_acquis_apprentissage_prerequis' => 4,
        ]);
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 1,
            'fk_acquis_apprentissage_prerequis' => 5,
        ]);
        DB::table('aavue_prerequis')->insert([
            'fk_unite_enseignement' => 1,
            'fk_acquis_apprentissage_prerequis' => 6,
        ]);
    }
}
