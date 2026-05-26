<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $this->addColumn($table, 'full_name', fn () => $table->string('full_name')->nullable()->after('employee_number'));
            $this->addColumn($table, 'nickname', fn () => $table->string('nickname', 100)->nullable()->after('full_name'));
            $this->addColumn($table, 'place_of_birth', fn () => $table->string('place_of_birth', 100)->nullable()->after('gender'));
            $this->addColumn($table, 'date_of_birth', fn () => $table->date('date_of_birth')->nullable()->after('place_of_birth'));
            $this->addColumn($table, 'religion', fn () => $table->string('religion', 20)->nullable()->after('date_of_birth'));
            $this->addColumn($table, 'blood_type', fn () => $table->string('blood_type', 5)->nullable()->after('religion'));
            $this->addColumn($table, 'marital_status', fn () => $table->string('marital_status', 20)->default('single')->after('blood_type'));
            $this->addColumn($table, 'nationality', fn () => $table->string('nationality', 100)->default('Indonesia')->after('marital_status'));
            $this->addColumn($table, 'number_of_children', fn () => $table->unsignedSmallInteger('number_of_children')->default(0)->after('nationality'));
            $this->addColumn($table, 'spouse_name', fn () => $table->string('spouse_name')->nullable()->after('number_of_children'));
            $this->addColumn($table, 'spouse_date_of_birth', fn () => $table->date('spouse_date_of_birth')->nullable()->after('spouse_name'));
            $this->addForeignColumn($table, 'division_id', 'divisions', 'department_id');
            $this->addForeignColumn($table, 'unit_id', 'units', 'division_id');
            $this->addForeignColumn($table, 'job_level_id', 'job_levels', 'employment_type_id');
            $this->addForeignColumn($table, 'work_location_id', 'work_locations', 'job_level_id');
            $this->addColumn($table, 'first_contract_date', fn () => $table->date('first_contract_date')->nullable()->after('join_date'));
            $this->addForeignColumn($table, 'termination_reason_id', 'termination_reasons', 'end_date');
            $this->addColumn($table, 'termination_notes', fn () => $table->text('termination_notes')->nullable()->after('termination_reason_id'));
            $this->addColumn($table, 'offboarding_started_at', fn () => $table->timestamp('offboarding_started_at')->nullable()->after('termination_notes'));
            $this->addForeignColumn($table, 'offboarding_started_by', 'users', 'offboarding_started_at');
            $this->addColumn($table, 'work_email', fn () => $table->string('work_email')->nullable()->after('offboarding_started_by'));
            $this->addColumn($table, 'work_phone', fn () => $table->string('work_phone', 30)->nullable()->after('work_email'));
            $this->addColumn($table, 'work_mobile', fn () => $table->string('work_mobile', 30)->nullable()->after('work_phone'));
            $this->addColumn($table, 'address_ktp', fn () => $table->text('address_ktp')->nullable()->after('work_mobile'));
            $this->addColumn($table, 'address_domisili', fn () => $table->text('address_domisili')->nullable()->after('address_ktp'));
            $this->addColumn($table, 'private_phone', fn () => $table->string('private_phone', 30)->nullable()->after('address_domisili'));
            $this->addColumn($table, 'private_email', fn () => $table->string('private_email')->nullable()->after('private_phone'));
            $this->addColumn($table, 'emergency_contact_name', fn () => $table->string('emergency_contact_name')->nullable()->after('private_email'));
            $this->addColumn($table, 'emergency_contact_phone', fn () => $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_name'));
            $this->addColumn($table, 'emergency_contact_relation', fn () => $table->string('emergency_contact_relation', 50)->nullable()->after('emergency_contact_phone'));
            $this->addColumn($table, 'bpjs_kesehatan_number', fn () => $table->string('bpjs_kesehatan_number', 30)->nullable()->after('emergency_contact_relation'));
            $this->addColumn($table, 'bpjs_ketenagakerjaan_number', fn () => $table->string('bpjs_ketenagakerjaan_number', 30)->nullable()->after('bpjs_kesehatan_number'));
            $this->addColumn($table, 'bank_account_name', fn () => $table->string('bank_account_name')->nullable()->after('bank_account_number'));
            $this->addColumn($table, 'work_permit_number', fn () => $table->string('work_permit_number', 100)->nullable()->after('bank_account_name'));
            $this->addColumn($table, 'work_permit_expiry', fn () => $table->date('work_permit_expiry')->nullable()->after('work_permit_number'));
            $this->addColumn($table, 'barcode', fn () => $table->string('barcode', 100)->nullable()->after('work_permit_expiry'));
            $this->addColumn($table, 'pin', fn () => $table->string('pin', 10)->nullable()->after('barcode'));
            $this->addColumn($table, 'consent_text', fn () => $table->text('consent_text')->nullable()->after('consent_by'));
        });

        Schema::table('employee_photos', function (Blueprint $table) {
            $this->addColumn($table, 'original_path', fn () => $table->string('original_path', 500)->nullable()->after('employee_id'));
            $this->addColumn($table, 'medium_path', fn () => $table->string('medium_path', 500)->nullable()->after('original_path'));
            $this->addColumn($table, 'thumbnail_path', fn () => $table->string('thumbnail_path', 500)->nullable()->after('medium_path'));
            $this->addColumn($table, 'file_size', fn () => $table->unsignedInteger('file_size')->nullable()->after('size_bytes'));
        });

        Schema::table('employee_documents', function (Blueprint $table) {
            $this->addColumn($table, 'file_path', fn () => $table->string('file_path', 500)->nullable()->after('document_type'));
            $this->addColumn($table, 'file_name', fn () => $table->string('file_name')->nullable()->after('file_path'));
            $this->addColumn($table, 'file_size', fn () => $table->unsignedInteger('file_size')->nullable()->after('size_bytes'));
            $this->addColumn($table, 'notes', fn () => $table->text('notes')->nullable()->after('file_size'));
            $this->addColumn($table, 'is_verified', fn () => $table->boolean('is_verified')->default(false)->after('notes'));
            $this->addForeignColumn($table, 'verified_by', 'users', 'is_verified');
            $this->addColumn($table, 'verified_at', fn () => $table->timestamp('verified_at')->nullable()->after('verified_by'));
        });
    }

    public function down(): void
    {
        // Additive migration: columns are intentionally retained to avoid data loss.
    }

    private function addColumn(Blueprint $table, string $column, callable $definition): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $definition();
        }
    }

    private function addForeignColumn(Blueprint $table, string $column, string $foreignTable, string $after): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $table->foreignId($column)->nullable()->after($after)->constrained($foreignTable)->nullOnDelete();
        }
    }
};
