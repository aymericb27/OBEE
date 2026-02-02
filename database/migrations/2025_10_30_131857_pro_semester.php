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
        Schema::create('pro_semester', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->integer('ects');
            $table->integer('semester');
            $table->foreignId("university_id")->constrained('universities');
            $table->foreignId("fk_programme")->constrained('programme');
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
