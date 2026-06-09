<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (! Schema::hasColumn('departments', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('departments', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('description')->constrained('departments')->nullOnDelete();
            }
        });

        Schema::table('positions', function (Blueprint $table) {
            if (! Schema::hasColumn('positions', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('name')->constrained('departments')->nullOnDelete();
            }
            if (! Schema::hasColumn('positions', 'description')) {
                $table->text('description')->nullable()->after('department_id');
            }
        });

        Schema::table('banks', function (Blueprint $table) {
            if (! Schema::hasColumn('banks', 'swift')) {
                $table->string('swift')->nullable()->after('name');
            }
        });

        Schema::table('document_types', function (Blueprint $table) {
            if (! Schema::hasColumn('document_types', 'is_mandatory')) {
                $table->boolean('is_mandatory')->default(false)->after('name');
            }
            if (! Schema::hasColumn('document_types', 'description')) {
                $table->text('description')->nullable()->after('is_mandatory');
            }
        });

        Schema::table('education_levels', function (Blueprint $table) {
            if (! Schema::hasColumn('education_levels', 'order')) {
                $table->integer('order')->default(0)->after('name');
            }
        });

        if (! Schema::hasTable('contract_types')) {
            Schema::create('contract_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
                $table->string('code');
                $table->string('name');
                $table->string('type', 20);
                $table->text('description')->nullable();
                $table->integer('max_duration_months')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('archived_at')->nullable();
                $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['company_id', 'code']);
                $table->index(['company_id', 'type']);
                $table->index(['company_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_types');

        Schema::table('education_levels', function (Blueprint $table) {
            if (Schema::hasColumn('education_levels', 'order')) {
                $table->dropColumn('order');
            }
        });

        Schema::table('document_types', function (Blueprint $table) {
            if (Schema::hasColumn('document_types', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('document_types', 'is_mandatory')) {
                $table->dropColumn('is_mandatory');
            }
        });

        Schema::table('banks', function (Blueprint $table) {
            if (Schema::hasColumn('banks', 'swift')) {
                $table->dropColumn('swift');
            }
        });

        Schema::table('positions', function (Blueprint $table) {
            if (Schema::hasColumn('positions', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('positions', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }
        });

        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
            if (Schema::hasColumn('departments', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
