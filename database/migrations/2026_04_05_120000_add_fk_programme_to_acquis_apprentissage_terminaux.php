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

        $programExists = DB::table('programme')->where('id', 1)->exists();
        if (!$programExists) {
            throw new \RuntimeException("Le programme id=1 n'existe pas.");
        }

        if (!Schema::hasColumn('acquis_apprentissage_terminaux', 'fk_programme')) {
            Schema::table('acquis_apprentissage_terminaux', function (Blueprint $table) {
                $table->foreignId('fk_programme')
                    ->nullable()
                    ->after('university_id');
            });
        }

        // Règle métier décidée: tous les AAT sont rattachés au programme 1.
        DB::table('acquis_apprentissage_terminaux')->update(['fk_programme' => 1]);

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
        if (!Schema::hasTable('acquis_apprentissage_terminaux')) {
            return;
        }

        Schema::table('acquis_apprentissage_terminaux', function (Blueprint $table) {
            $table->dropForeign('aat_fk_programme_foreign');
            $table->dropIndex('aat_uni_programme_index');
            $table->dropColumn('fk_programme');
        });
    }
};
