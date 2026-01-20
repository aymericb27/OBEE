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
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('fk_ue_parent')
                ->constrained('unite_enseignement')
                ->onDelete('cascade');
            $table->foreignId('fk_ue_child')
                ->constrained('unite_enseignement')
                ->onDelete('cascade');
            $table->foreignId("university_id")->constrained('universities');

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
