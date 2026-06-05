<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_family', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('name');
            $table->string('relationship', 20);
            $table->date('birth_date')->nullable();
            $table->string('gender', 20);
            $table->string('occupation')->nullable();
            $table->string('phone', 50)->nullable();
            $table->boolean('is_dependent')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
            $table->index(['company_id', 'relationship']);
            $table->index(['company_id', 'is_dependent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_family');
    }
};
