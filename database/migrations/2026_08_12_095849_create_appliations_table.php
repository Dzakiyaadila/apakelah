<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('job_listings')->nullOnDelete();
            $table->string('company');
            $table->string('position');
            $table->date('applied_date')->nullable();
            $table->date('deadline')->nullable();
            $table->string('type')->nullable();
            $table->string('contact')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['not_started', 'applied', 'interview', 'offer', 'rejected', 'no_reply'])
                ->default('not_started');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};