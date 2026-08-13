<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('company');
            $table->string('location')->nullable();
            $table->enum('type', ['fulltime', 'contract', 'internship'])->default('fulltime');
            $table->enum('work_mode', ['remote', 'onsite', 'hybrid'])->default('onsite');
            $table->text('description')->nullable();
            $table->json('required_skills')->nullable();
            $table->string('salary_range')->nullable();
            $table->string('logo_url')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};