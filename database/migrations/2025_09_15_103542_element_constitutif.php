<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('element_constitutif', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('code');
            $table->text('description');
            $table->integer('volume_horaire');
            $table->integer('fk_unite_enseignement')->unsigned()->nullable();
            $table->foreign('fk_unite_enseignement')->references('id')->on('unite_enseignement');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ECelement_constitutif');
    }
};
