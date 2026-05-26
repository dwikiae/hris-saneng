<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        /*
         * Backup step required by AGENTS.md RULE-B3:
         * before applying this destructive schema cleanup on a non-local database,
         * export the employees table using the command documented in
         * docs/migration-backups/2026_05_25_remove_employee_payroll_columns.md.
         */
        $columns = array_values(array_filter(
            ['salary', 'allowances', 'deductions'],
            fn (string $column): bool => Schema::hasColumn('employees', $column)
        ));

        if ($columns === []) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                $table->dropColumn($column);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        $columns = array_values(array_filter(
            ['salary', 'allowances', 'deductions'],
            fn (string $column): bool => ! Schema::hasColumn('employees', $column)
        ));

        if ($columns === []) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                $table->text($column)->nullable();
            }
        });
    }
};
