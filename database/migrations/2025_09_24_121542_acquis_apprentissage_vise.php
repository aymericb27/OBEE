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
        Schema::create('acquis_apprentissage_vise', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->text('name');
            $table->integer('fk_AAT')->unsigned()->nullable();
            $table->foreign('fk_AAT')->references('id')->on('acquis_apprentissage_vise');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acquis_apprentissage_vise');
    }
};
