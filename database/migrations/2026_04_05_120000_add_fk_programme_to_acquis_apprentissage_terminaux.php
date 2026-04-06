<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('acquis_apprentissage_terminaux')) {
            return;
        }

        if (!Schema::hasColumn('acquis_apprentissage_terminaux', 'fk_programme')) {
            Schema::table('acquis_apprentissage_terminaux', function (Blueprint $table) {
                $table->foreignId('fk_programme')
                    ->nullable()
                    ->after('university_id');
            });
        }

        if (Schema::hasTable('aav_aat') && Schema::hasColumn('aav_aat', 'fk_programme')) {
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
        }

        if (Schema::hasTable('ue_aat') && Schema::hasTable('ue_programme')) {
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
        }

        if (Schema::hasTable('aav_aat') && Schema::hasTable('aavue_vise') && Schema::hasTable('ue_programme')) {
            DB::statement("
                UPDATE acquis_apprentissage_terminaux AS aat
                JOIN (
                    SELECT aa.fk_aat, aa.university_id, MIN(up.fk_programme) AS fk_programme
                    FROM aav_aat AS aa
                    JOIN aavue_vise AS av
                        ON av.fk_acquis_apprentissage_vise = aa.fk_aav
                        AND av.university_id = aa.university_id
                    JOIN ue_programme AS up
                        ON up.fk_unite_enseignement = av.fk_unite_enseignement
                        AND up.university_id = aa.university_id
                    GROUP BY aa.fk_aat, aa.university_id
                ) AS src
                    ON src.fk_aat = aat.id
                    AND src.university_id = aat.university_id
                SET aat.fk_programme = src.fk_programme
                WHERE aat.fk_programme IS NULL
            ");
        }

        if (Schema::hasTable('aav_aat') && Schema::hasTable('aavue_prerequis') && Schema::hasTable('ue_programme')) {
            DB::statement("
                UPDATE acquis_apprentissage_terminaux AS aat
                JOIN (
                    SELECT aa.fk_aat, aa.university_id, MIN(up.fk_programme) AS fk_programme
                    FROM aav_aat AS aa
                    JOIN aavue_prerequis AS apu
                        ON apu.fk_acquis_apprentissage_prerequis = aa.fk_aav
                        AND apu.university_id = aa.university_id
                    JOIN ue_programme AS up
                        ON up.fk_unite_enseignement = apu.fk_unite_enseignement
                        AND up.university_id = aa.university_id
                    GROUP BY aa.fk_aat, aa.university_id
                ) AS src
                    ON src.fk_aat = aat.id
                    AND src.university_id = aat.university_id
                SET aat.fk_programme = src.fk_programme
                WHERE aat.fk_programme IS NULL
            ");
        }

        if (Schema::hasTable('aav_aat') && Schema::hasTable('aavpro_prerequis')) {
            DB::statement("
                UPDATE acquis_apprentissage_terminaux AS aat
                JOIN (
                    SELECT aa.fk_aat, aa.university_id, MIN(app.fk_programme) AS fk_programme
                    FROM aav_aat AS aa
                    JOIN aavpro_prerequis AS app
                        ON app.fk_acquis_apprentissage_prerequis = aa.fk_aav
                        AND app.university_id = aa.university_id
                    GROUP BY aa.fk_aat, aa.university_id
                ) AS src
                    ON src.fk_aat = aat.id
                    AND src.university_id = aat.university_id
                SET aat.fk_programme = src.fk_programme
                WHERE aat.fk_programme IS NULL
            ");
        }

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

        if (!$this->foreignKeyExists('acquis_apprentissage_terminaux', 'aat_fk_programme_foreign')) {
            Schema::table('acquis_apprentissage_terminaux', function (Blueprint $table) {
                $table->foreign('fk_programme', 'aat_fk_programme_foreign')
                    ->references('id')
                    ->on('programme')
                    ->onDelete('cascade');
            });
        }

        if (!$this->indexExists('acquis_apprentissage_terminaux', 'aat_uni_programme_index')) {
            Schema::table('acquis_apprentissage_terminaux', function (Blueprint $table) {
                $table->index(['university_id', 'fk_programme'], 'aat_uni_programme_index');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('acquis_apprentissage_terminaux')) {
            return;
        }

        if ($this->foreignKeyExists('acquis_apprentissage_terminaux', 'aat_fk_programme_foreign')) {
            Schema::table('acquis_apprentissage_terminaux', function (Blueprint $table) {
                $table->dropForeign('aat_fk_programme_foreign');
            });
        }

        if ($this->indexExists('acquis_apprentissage_terminaux', 'aat_uni_programme_index')) {
            Schema::table('acquis_apprentissage_terminaux', function (Blueprint $table) {
                $table->dropIndex('aat_uni_programme_index');
            });
        }

        if (Schema::hasColumn('acquis_apprentissage_terminaux', 'fk_programme')) {
            Schema::table('acquis_apprentissage_terminaux', function (Blueprint $table) {
                $table->dropColumn('fk_programme');
            });
        }
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $tableName)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }

    private function foreignKeyExists(string $tableName, string $constraintName): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $tableName)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
