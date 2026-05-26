<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createEmployeeEducations();
        $this->createEmployeeExperiences();
        $this->createEmployeeFamilyMembers();
        $this->createContracts();
        $this->createEmployeeOffboarding();
        $this->createEmployeeOffboardingItems();
        $this->createEmployeeChatterMessages();
        $this->createEmployeeChatterMentions();
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_chatter_mentions');
        Schema::dropIfExists('employee_chatter_messages');
        Schema::dropIfExists('employee_offboarding_items');
        Schema::dropIfExists('employee_offboarding');
        DB::statement('DROP INDEX IF EXISTS idx_one_active_contract');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('employee_family_members');
        Schema::dropIfExists('employee_experiences');
        Schema::dropIfExists('employee_educations');
    }

    private function createEmployeeEducations(): void
    {
        if (Schema::hasTable('employee_educations')) {
            return;
        }

        Schema::create('employee_educations', function (Blueprint $table) {
            $table->id();
            $this->employeeOwnerColumns($table);
            $table->string('education_level', 50)->nullable();
            $table->string('institution_name');
            $table->string('field_of_study')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->decimal('gpa', 4, 2)->nullable();
            $this->auditColumns($table, false);
        });
    }

    private function createEmployeeExperiences(): void
    {
        if (Schema::hasTable('employee_experiences')) {
            return;
        }

        Schema::create('employee_experiences', function (Blueprint $table) {
            $table->id();
            $this->employeeOwnerColumns($table);
            $table->string('company_name');
            $table->string('position');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $this->auditColumns($table, false);
        });
    }

    private function createEmployeeFamilyMembers(): void
    {
        if (Schema::hasTable('employee_family_members')) {
            return;
        }

        Schema::create('employee_family_members', function (Blueprint $table) {
            $table->id();
            $this->employeeOwnerColumns($table);
            $table->string('relation', 50)->nullable();
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->string('occupation')->nullable();
            $table->boolean('is_dependent')->default(false);
            $this->auditColumns($table, false);
        });
    }

    private function createContracts(): void
    {
        if (Schema::hasTable('contracts')) {
            return;
        }

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $this->employeeOwnerColumns($table);
            $table->string('contract_number', 100)->unique();
            $table->foreignId('contract_type_id')->constrained('contract_types')->restrictOnDelete();
            $table->foreignId('position_id')->constrained('positions')->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('work_location_id')->nullable()->constrained('work_locations')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('termination_reason', 50)->nullable();
            $table->date('termination_date')->nullable();
            $table->text('termination_notes')->nullable();
            $table->string('document_path', 500)->nullable();
            $table->date('signed_date')->nullable();
            $this->approvalColumns($table);
            $this->auditColumns($table);
        });

        DB::statement("CREATE UNIQUE INDEX idx_one_active_contract ON contracts(employee_id) WHERE status = 'active'");
    }

    private function createEmployeeOffboarding(): void
    {
        if (Schema::hasTable('employee_offboarding')) {
            return;
        }

        Schema::create('employee_offboarding', function (Blueprint $table) {
            $table->id();
            $this->employeeOwnerColumns($table);
            $table->foreignId('termination_reason_id')->constrained('termination_reasons')->restrictOnDelete();
            $table->date('termination_date');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('in_progress');
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    private function createEmployeeOffboardingItems(): void
    {
        if (Schema::hasTable('employee_offboarding_items')) {
            return;
        }

        Schema::create('employee_offboarding_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_offboarding_id')->constrained('employee_offboarding')->restrictOnDelete();
            $table->foreignId('offboarding_template_item_id')->nullable()->constrained('offboarding_template_items')->nullOnDelete();
            $table->string('item_name');
            $table->string('department_responsible', 100)->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_done')->default(false);
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('done_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    private function createEmployeeChatterMessages(): void
    {
        if (Schema::hasTable('employee_chatter_messages')) {
            return;
        }

        Schema::create('employee_chatter_messages', function (Blueprint $table) {
            $table->id();
            $this->employeeOwnerColumns($table);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->default('manual_message');
            $table->text('message');
            $table->timestamps();
            $table->index(['employee_id', 'created_at'], 'idx_chatter_employee');
        });
    }

    private function createEmployeeChatterMentions(): void
    {
        if (Schema::hasTable('employee_chatter_mentions')) {
            return;
        }

        Schema::create('employee_chatter_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('chatter_message_id')->constrained('employee_chatter_messages')->restrictOnDelete();
            $table->foreignId('mentioned_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    private function employeeOwnerColumns(Blueprint $table): void
    {
        $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
        $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
        $table->index(['company_id', 'employee_id']);
    }

    private function approvalColumns(Blueprint $table): void
    {
        $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('approved_at')->nullable();
    }

    private function auditColumns(Blueprint $table, bool $archivable = true): void
    {
        if ($archivable) {
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
        }

        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
    }
};
