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
            AdminUserSeeder::class,
            ProgrammeSeeder::class,
            ProSemesterSeeder::class,
            AcquisApprentissageTerminauxSeeder::class,
            UniteEnseignementSeeder::class,
            UeAatSeeder::class,
            AcquisApprentissageViseSeeder::class,
            AAVUESeeder::class,
            UEProgrammeSeeder::class,
            AAVAATSeeder::class,
        ]);
    }
}
