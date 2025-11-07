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
        Schema::create('unite_enseignement', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->date('date_begin');
            $table->date('date_end');
            $table->text('description');
            $table->integer('ects');
            $table->integer('semestre');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unite_enseignement');
    }
};
