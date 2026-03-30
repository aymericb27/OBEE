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
        Schema::create('ue_prerequis', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('fk_UE_prerequis')
                ->constrained('unite_enseignement')
                ->onDelete('cascade');
            $table->foreignId('fk_UE_parent')
                ->constrained('unite_enseignement')
                ->onDelete('cascade');
            $table->foreignId('university_id')
                ->constrained('universities')
                ->cascadeOnDelete();

            $table->unique(['university_id', 'fk_UE_prerequis', 'fk_UE_parent'], 'ue_prerequis_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ue_prerequis');
    }
};
