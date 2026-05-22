<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('applicant_id')->constrained('applicants')->restrictOnDelete();
            $table->foreignId('job_posting_id')->constrained('job_postings')->restrictOnDelete();
            $table->foreignId('interviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('scheduled_at');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->string('location')->nullable();
            $table->string('confirmation_status', 30)->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->string('status', 30)->default('scheduled');
            $table->unsignedInteger('timer_snapshot')->nullable();
            $table->decimal('passing_grade_snapshot', 5, 2)->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->text('result_notes')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'applicant_id']);
            $table->index(['company_id', 'scheduled_at']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_schedules');
    }
};
