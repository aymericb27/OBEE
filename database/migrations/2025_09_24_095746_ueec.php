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
        Schema::create('ueec', function (Blueprint $table) {
            $table->id();
            $table->integer('fk_unite_enseignement')->unsigned()->nullable();
            $table->foreign('fk_unite_enseignement')->references('id')->on('unite_enseignement');
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
        Schema::dropIfExists('ueec');
    }
};
