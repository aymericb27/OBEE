<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acquis_apprentissage_terminaux', function (Blueprint $table) {
            $table->foreignId('fk_programme')
                ->nullable()
                ->after('university_id');
        });

        DB::statement("
            UPDATE acquis_apprentissage_terminaux AS aat
            JOIN (
                SELECT fk_aat, university_id, MIN(fk_programme) AS fk_programme
                FROM aav_aat
                WHERE fk_programme IS NOT NULL
                GROUP BY fk_aat, university_id
            ) AS src
                ON src.fk_aat = aat.id
                AND src.university_id = aat.university_id
            SET aat.fk_programme = src.fk_programme
            WHERE aat.fk_programme IS NULL
        ");

        DB::statement("
            UPDATE acquis_apprentissage_terminaux AS aat
            JOIN (
                SELECT ua.fk_aat, ua.university_id, MIN(up.fk_programme) AS fk_programme
                FROM ue_aat AS ua
                JOIN ue_programme AS up
                    ON up.fk_unite_enseignement = ua.fk_ue
                    AND up.university_id = ua.university_id
                GROUP BY ua.fk_aat, ua.university_id
            ) AS src
                ON src.fk_aat = aat.id
                AND src.university_id = aat.university_id
            SET aat.fk_programme = src.fk_programme
            WHERE aat.fk_programme IS NULL
        ");

        $singleProgramByUniversity = DB::table('programme')
            ->select(
                'university_id',
                DB::raw('MIN(id) AS program_id'),
                DB::raw('COUNT(*) AS total')
            )
            ->groupBy('university_id')
            ->having('total', '=', 1)
            ->pluck('program_id', 'university_id');

        foreach ($singleProgramByUniversity as $universityId => $programId) {
            DB::table('acquis_apprentissage_terminaux')
                ->where('university_id', (int) $universityId)
                ->whereNull('fk_programme')
                ->update(['fk_programme' => (int) $programId]);
        }

        $unresolved = DB::table('acquis_apprentissage_terminaux')
            ->whereNull('fk_programme')
            ->select('id', 'code', 'university_id')
            ->orderBy('id')
            ->get();

        if ($unresolved->isNotEmpty()) {
            $preview = $unresolved
                ->take(20)
                ->map(fn($row) => "id={$row->id},code={$row->code},university={$row->university_id}")
                ->implode('; ');

            throw new \RuntimeException(
                "Impossible d'assigner un programme a certains AAT. "
                    . "Assignez un programme a ces AAT puis relancez la migration. Exemples: {$preview}"
            );
        }

        DB::statement('ALTER TABLE acquis_apprentissage_terminaux MODIFY fk_programme BIGINT UNSIGNED NOT NULL');

        Schema::table('acquis_apprentissage_terminaux', function (Blueprint $table) {
            $table->foreign('fk_programme', 'aat_fk_programme_foreign')
                ->references('id')
                ->on('programme')
                ->onDelete('cascade');
            $table->index(['university_id', 'fk_programme'], 'aat_uni_programme_index');
        });
    }

    public function down(): void
    {
        Schema::table('acquis_apprentissage_terminaux', function (Blueprint $table) {
            $table->dropForeign('aat_fk_programme_foreign');
            $table->dropIndex('aat_uni_programme_index');
            $table->dropColumn('fk_programme');
        });
    }
};
