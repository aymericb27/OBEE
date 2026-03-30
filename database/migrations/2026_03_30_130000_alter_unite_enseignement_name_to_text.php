<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE unite_enseignement MODIFY name TEXT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE unite_enseignement SET name = LEFT(name, 255) WHERE CHAR_LENGTH(name) > 255');
        DB::statement('ALTER TABLE unite_enseignement MODIFY name VARCHAR(255) NOT NULL');
    }
};
