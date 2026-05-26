<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasColumn('employees', 'employee_number')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Intentionally not reverted: multiple pending employees can have no number.
    }
};
