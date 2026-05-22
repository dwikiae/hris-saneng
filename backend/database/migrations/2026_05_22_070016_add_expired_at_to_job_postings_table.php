<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->timestamp('expired_at')->nullable()->after('published_at');
            $table->index(['company_id', 'status', 'expired_at']);
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status', 'expired_at']);
            $table->dropColumn('expired_at');
        });
    }
};
