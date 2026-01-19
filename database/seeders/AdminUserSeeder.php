<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\University;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Créer ou récupérer une université
        $university = University::firstOrCreate(
            ['name' => 'UCLouvain'],
            []
        );
        $university2 = University::firstOrCreate(
            ['name' => 'EPHEC'],
            []
        );


        // 2) Créer ou récupérer l'admin
        User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Admin',
                'firstname' => 'Super',
                'password' => Hash::make('Admin123'), // change ça après
                'role' => 'admin',
                'is_approved' => true,
                'approved_at' => now(),
                'university_id' => $university->id,
                'email_verified_at' => now(),
            ]
        );
        User::updateOrCreate(
            ['email' => 'aymeric@demo.com'],
            [
                'name' => 'aymeric',
                'firstname' => 'Super',
                'password' => Hash::make('Admin123'), // change ça après
                'role' => 'user',
                'is_approved' => true,
                'approved_at' => now(),
                'university_id' => $university2->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
