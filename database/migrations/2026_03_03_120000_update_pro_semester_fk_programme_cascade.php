<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pro_semester', function (Blueprint $table) {
            $table->dropForeign(['fk_programme']);
            $table->foreign('fk_programme')
                ->references('id')
                ->on('programme')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pro_semester', function (Blueprint $table) {
            $table->dropForeign(['fk_programme']);
            $table->foreign('fk_programme')
                ->references('id')
                ->on('programme');
        });
    }
};
