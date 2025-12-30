<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AcquisApprentissageTerminauxSeeder::class,
            UniteEnseignementSeeder::class,
            UeAatSeeder::class,
            AcquisApprentissageViseSeeder::class,
            AAVUESeeder::class,
            ProgrammeSeeder::class,
            UEProgrammeSeeder::class,
            AAVAATSeeder::class,
        ]);
    }
}
