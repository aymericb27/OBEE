<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ue_aat', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('fk_ue')
                ->constrained('unite_enseignement')
                ->onDelete('cascade');
            $table->foreignId('fk_aat')
                ->constrained('acquis_apprentissage_terminaux')
                ->onDelete('cascade');

            $table->integer('contribution')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ue_aat');
    }
};
