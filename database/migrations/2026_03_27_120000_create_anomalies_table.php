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
        Schema::create('anomalies', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();

            $table->foreignId('ue_id')
                ->nullable()
                ->constrained('unite_enseignement')
                ->nullOnDelete();

            $table->foreignId('program_id')
                ->nullable()
                ->constrained('programme')
                ->nullOnDelete();

            $table->foreignId('semester_id')
                ->nullable()
                ->constrained('pro_semester')
                ->nullOnDelete();

            $table->foreignId('university_id')
                ->constrained('universities')
                ->cascadeOnDelete();

            $table->string('code', 64);
            $table->string('severity', 16)->default('warning');
            $table->text('message');
            $table->json('details')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('detected_at')->nullable();
            $table->timestamps();

            $table->index(['university_id', 'ue_id']);
            $table->index(['university_id', 'program_id', 'semester_id']);
            $table->index(['code', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anomalies');
    }
};
