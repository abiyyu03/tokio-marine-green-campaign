<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->string('choice')->nullable();
            $table->integer('score')->default(0);
            $table->string('label');
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->enum('question_type', ['essay', 'single_choice', 'multiple_choice', 'linear_scale']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
