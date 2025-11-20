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
        Schema::create('element_constitutif', function (Blueprint $table) {
            $table->id();
            $table->integer('fk_ue_parent')->unsigned()->nullable();
            $table->foreign('fk_ue_parent')->references('id')->on('unite_enseignement');
            $table->integer('fk_ue_child')->unsigned()->nullable();
            $table->foreign('fk_ue_child')->references('id')->on('unite_enseignement');
            $table->integer('contribution');
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
