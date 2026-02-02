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
        Schema::create('acquis_apprentissage_terminaux', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('code');
            $table->text('name');
            $table->text('description')->nullable();
            $table->foreignId("university_id")->constrained('universities');
            $table->unique(['university_id', 'code'], 'aat_university_code_unique');
            $table->integer('level_contribution')->default(3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acquis_apprentissage_terminaux');
    }
};
