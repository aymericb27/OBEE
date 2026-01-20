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
        Schema::create('aavue_prerequis', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('fk_acquis_apprentissage_prerequis')
                ->constrained('acquis_apprentissage_vise')
                ->onDelete('cascade');
            $table->foreignId('fk_unite_enseignement')
                ->constrained('unite_enseignement')
                ->onDelete('cascade');
            $table->foreignId("university_id")->constrained('universities');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aavue_prerequis');
    }
};
