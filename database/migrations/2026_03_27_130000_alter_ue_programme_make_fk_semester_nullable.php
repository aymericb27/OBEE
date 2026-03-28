<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE ue_programme DROP FOREIGN KEY ue_programme_fk_semester_foreign');
        DB::statement('ALTER TABLE ue_programme MODIFY fk_semester BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE ue_programme ADD CONSTRAINT ue_programme_fk_semester_foreign FOREIGN KEY (fk_semester) REFERENCES pro_semester(id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        DB::statement('DELETE FROM ue_programme WHERE fk_semester IS NULL');
        DB::statement('ALTER TABLE ue_programme DROP FOREIGN KEY ue_programme_fk_semester_foreign');
        DB::statement('ALTER TABLE ue_programme MODIFY fk_semester BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE ue_programme ADD CONSTRAINT ue_programme_fk_semester_foreign FOREIGN KEY (fk_semester) REFERENCES pro_semester(id) ON DELETE CASCADE');
    }
};

