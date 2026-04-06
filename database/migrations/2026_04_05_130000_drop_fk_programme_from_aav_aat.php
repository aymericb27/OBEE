<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('aav_aat')) {
            return;
        }

        DB::statement("
            DELETE aa
            FROM aav_aat aa
            INNER JOIN (
                SELECT MIN(id) AS keep_id, university_id, fk_aav, fk_aat
                FROM aav_aat
                GROUP BY university_id, fk_aav, fk_aat
            ) dedup
                ON dedup.university_id = aa.university_id
                AND dedup.fk_aav = aa.fk_aav
                AND dedup.fk_aat = aa.fk_aat
            WHERE aa.id <> dedup.keep_id
        ");

        // aav_aat.university_id has a FK to universities, so ensure it has its own
        // dedicated index before dropping composite indexes that start with university_id.
        if (!$this->indexExists('aav_aat', 'aav_aat_university_id_index')) {
            DB::statement('ALTER TABLE `aav_aat` ADD INDEX `aav_aat_university_id_index` (`university_id`)');
        }

        $this->dropForeignKeysOnColumn('aav_aat', 'fk_programme');
        $this->dropIndexIfExists('aav_aat', 'aav_aat_uni_aav_aat_prog_unique');
        $this->dropIndexIfExists('aav_aat', 'aav_aat_uni_prog_index');
        $this->dropIndexIfExists('aav_aat', 'aav_aat_fk_programme_foreign');

        if (Schema::hasColumn('aav_aat', 'fk_programme')) {
            DB::statement('ALTER TABLE `aav_aat` DROP COLUMN `fk_programme`');
        }

        if (!$this->indexExists('aav_aat', 'aav_aat_uni_aav_aat_unique')) {
            DB::statement('ALTER TABLE `aav_aat` ADD UNIQUE `aav_aat_uni_aav_aat_unique` (`university_id`, `fk_aav`, `fk_aat`)');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('aav_aat')) {
            return;
        }

        $this->dropIndexIfExists('aav_aat', 'aav_aat_uni_aav_aat_unique');

        if (!Schema::hasColumn('aav_aat', 'fk_programme')) {
            Schema::table('aav_aat', function (Blueprint $table) {
                $table->foreignId('fk_programme')
                    ->nullable()
                    ->after('fk_aat')
                    ->constrained('programme')
                    ->onDelete('cascade');

                $table->unique(
                    ['university_id', 'fk_aav', 'fk_aat', 'fk_programme'],
                    'aav_aat_uni_aav_aat_prog_unique'
                );
                $table->index(['university_id', 'fk_programme'], 'aav_aat_uni_prog_index');
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

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            DB::statement(sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $tableName, $indexName));
        }
    }

    private function dropForeignKeysOnColumn(string $tableName, string $columnName): void
    {
        $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME');

        foreach ($foreignKeys as $foreignKey) {
            DB::statement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $tableName, $foreignKey));
        }
    }
};
