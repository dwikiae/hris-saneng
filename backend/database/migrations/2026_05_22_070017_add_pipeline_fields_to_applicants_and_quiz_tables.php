<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->boolean('is_duplicate')->default(false)->after('status');
            $table->boolean('is_blacklisted')->default(false)->after('is_duplicate');
            $table->text('rejection_reason')->nullable()->after('is_blacklisted');
        });

        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->timestamp('finished_at')->nullable()->after('submitted_at');
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->decimal('bobot_snapshot', 5, 2)->default(0)->after('is_correct');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropColumn('bobot_snapshot');
        });

        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropColumn('finished_at');
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn(['is_duplicate', 'is_blacklisted', 'rejection_reason']);
        });
    }
};
