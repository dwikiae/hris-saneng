<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('provinces')) {
            Schema::create('provinces', function (Blueprint $table) {
                $table->string('code', 2)->primary();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->string('code', 5)->primary();
                $table->string('province_code', 2);
                $table->string('name');
                $table->timestamps();

                $table->foreign('province_code')->references('code')->on('provinces')->restrictOnDelete();
                $table->index('province_code');
            });
        }

        if (! Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->string('code', 2)->primary();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('work_locations')) {
            Schema::create('work_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
                $table->string('code');
                $table->string('name');
                $table->text('address')->nullable();
                $table->string('city_id', 5)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('archived_at')->nullable();
                $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->foreign('city_id')->references('code')->on('cities')->nullOnDelete();
                $table->unique(['company_id', 'code']);
                $table->index(['company_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('employee_levels')) {
            Schema::create('employee_levels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
                $table->string('code');
                $table->string('name');
                $table->text('description')->nullable();
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('archived_at')->nullable();
                $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['company_id', 'code']);
                $table->index(['company_id', 'is_active']);
                $table->index(['company_id', 'order']);
            });
        }

        if (! Schema::hasTable('employee_module_settings')) {
            Schema::create('employee_module_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
                $table->string('key');
                $table->text('value')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_module_settings');
        Schema::dropIfExists('employee_levels');
        Schema::dropIfExists('work_locations');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('provinces');
    }
};
