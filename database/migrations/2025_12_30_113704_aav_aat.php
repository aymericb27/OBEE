<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aav_aat', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('fk_aav')
                ->constrained('acquis_apprentissage_vise')
                ->onDelete('cascade');
            $table->foreignId('fk_aat')
                ->constrained('acquis_apprentissage_terminaux')
                ->onDelete('cascade');
            $table->foreignId("university_id")->constrained('universities');

            $table->integer('contribution')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aav_aat');
    }
};
