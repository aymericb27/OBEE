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
        Schema::create('calendar_lesson_recursive', function (Blueprint $table) {
            $table->id();
            $table->time('time_begin');
            $table->time('time_end');
            $table->date('date_lesson_begin');
            $table->date('date_lesson_end');
            $table->integer('day_week');
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
            Schema::dropIfExists('calendar_lesson_recursive');
    }
};
