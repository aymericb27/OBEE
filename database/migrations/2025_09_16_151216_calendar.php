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
        Schema::create('calendar', function (Blueprint $table) {
            $table->id();
            $table->datetime('date_debut');
            $table->datetime('date_fin');
            $table->integer('fk_element_constitutif')->unsigned()->nullable();
            $table->foreign('fk_element_constitutif')->references('id')->on('element_constitutif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar');
    }
};
