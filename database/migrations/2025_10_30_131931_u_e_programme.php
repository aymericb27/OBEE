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
        Schema::create('ue_programme', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('fk_unite_enseignement')
                ->constrained('unite_enseignement')
                ->onDelete('cascade');
            $table->foreignId('fk_programme')
                ->constrained('programme')
                ->onDelete('cascade');
            $table->foreignId('university_id')
                ->constrained('universities')
                ->cascadeOnDelete();
            $table->foreignId('fk_semester')
                ->constrained('pro_semester')
                ->cascadeOnDelete();
            $table->unique(['university_id', 'fk_unite_enseignement', 'fk_programme', 'fk_semester'], 'ue_prog_uni_ue_prog_sem_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
