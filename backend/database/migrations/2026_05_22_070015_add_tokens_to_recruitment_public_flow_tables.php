<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->string('token')->nullable()->after('test_id');
            $table->unique(['company_id', 'token']);
        });

        Schema::table('interview_schedules', function (Blueprint $table) {
            $table->string('token')->nullable()->after('interviewer_id');
            $table->unique(['company_id', 'token']);
        });

        Schema::table('applicant_documents', function (Blueprint $table) {
            $table->string('token')->nullable()->after('document_type');
            $table->index(['company_id', 'token']);
        });
    }

    public function down(): void
    {
        Schema::table('applicant_documents', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'token']);
            $table->dropColumn('token');
        });

        Schema::table('interview_schedules', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'token']);
            $table->dropColumn('token');
        });

        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'token']);
            $table->dropColumn('token');
        });
    }
};
