<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('job_listings')->nullOnDelete();
            $table->unsignedTinyInteger('ats_score');
            $table->string('headline')->nullable();
            $table->json('strengths')->nullable();
            $table->json('improvements')->nullable();
            $table->json('suggestions')->nullable();
            $table->json('keyword_gaps')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_analyses');
    }
};