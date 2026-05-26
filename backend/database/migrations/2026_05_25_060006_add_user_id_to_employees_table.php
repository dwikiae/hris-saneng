<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees') || Schema::hasColumn('employees', 'user_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('approver_id')->unique()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Additive Sprint 6 compatibility column is intentionally retained.
    }
};
