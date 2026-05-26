<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('divisions')) {
            Schema::create('divisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
                $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
                $table->string('code');
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $this->auditColumns($table);
                $table->unique(['company_id', 'code']);
                $table->index(['company_id', 'department_id']);
            });
        }

        if (! Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
                $table->foreignId('division_id')->nullable()->constrained('divisions')->restrictOnDelete();
                $table->string('code');
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $this->auditColumns($table);
                $table->unique(['company_id', 'code']);
                $table->index(['company_id', 'division_id']);
            });
        }

        if (! Schema::hasTable('job_levels')) {
            Schema::create('job_levels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
                $table->string('code');
                $table->string('name');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $this->auditColumns($table);
                $table->unique(['company_id', 'code']);
            });
        }

        if (! Schema::hasTable('work_locations')) {
            Schema::create('work_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
                $table->string('code');
                $table->string('name');
                $table->text('address')->nullable();
                $table->boolean('is_active')->default(true);
                $this->auditColumns($table);
                $table->unique(['company_id', 'code']);
            });
        }

        if (! Schema::hasTable('contract_types')) {
            Schema::create('contract_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
                $table->string('code');
                $table->string('name');
                $table->boolean('requires_end_date')->default(false);
                $table->boolean('is_active')->default(true);
                $this->auditColumns($table);
                $table->unique(['company_id', 'code']);
            });
        }

        if (! Schema::hasTable('termination_reasons')) {
            Schema::create('termination_reasons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
                $table->string('code', 50);
                $table->string('name', 100);
                $table->boolean('is_active')->default(true);
                $this->auditColumns($table);
                $table->unique(['company_id', 'code']);
            });
        }

        if (! Schema::hasTable('offboarding_templates')) {
            Schema::create('offboarding_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
                $table->foreignId('termination_reason_id')->constrained('termination_reasons')->restrictOnDelete();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $this->auditColumns($table);
                $table->index(['company_id', 'termination_reason_id']);
            });
        }

        if (! Schema::hasTable('offboarding_template_items')) {
            Schema::create('offboarding_template_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
                $table->foreignId('template_id')->constrained('offboarding_templates')->restrictOnDelete();
                $table->string('item_name');
                $table->string('department_responsible', 100)->nullable();
                $table->boolean('is_mandatory')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $this->auditColumns($table);
                $table->index(['company_id', 'template_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('offboarding_template_items');
        Schema::dropIfExists('offboarding_templates');
        Schema::dropIfExists('termination_reasons');
        Schema::dropIfExists('contract_types');
        Schema::dropIfExists('work_locations');
        Schema::dropIfExists('job_levels');
        Schema::dropIfExists('units');
        Schema::dropIfExists('divisions');
    }

    private function auditColumns(Blueprint $table): void
    {
        $table->timestamp('archived_at')->nullable();
        $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
    }
};
