<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::table('aav_aat', function (Blueprint $table) {
            $table->dropUnique('aav_aat_uni_aav_aat_prog_unique');
            $table->dropIndex('aav_aat_uni_prog_index');
            $table->dropForeign(['fk_programme']);
            $table->dropColumn('fk_programme');
        });
    }
};

