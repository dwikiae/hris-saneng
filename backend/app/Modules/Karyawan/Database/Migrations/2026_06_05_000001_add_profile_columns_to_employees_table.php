<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('nickname')->nullable()->after('name');
            $table->foreignId('religion_id')->nullable()->after('gender')->constrained('religions')->nullOnDelete();
            $table->foreignId('marital_status_id')->nullable()->after('religion_id')->constrained('marital_statuses')->nullOnDelete();
            $table->foreignId('blood_type_id')->nullable()->after('marital_status_id')->constrained('blood_types')->nullOnDelete();
            $table->string('nationality')->nullable()->default('WNI')->after('blood_type_id');
            $table->text('passport_number')->nullable()->after('nationality');
            $table->string('province_id', 2)->nullable()->after('address');
            $table->string('city_id', 5)->nullable()->after('province_id');
            $table->text('domicile_address')->nullable()->after('city_id');
            $table->string('domicile_province_id', 2)->nullable()->after('domicile_address');
            $table->string('domicile_city_id', 5)->nullable()->after('domicile_province_id');
            $table->string('country_of_birth')->nullable()->after('birth_place');
            $table->foreignId('employee_level_id')->nullable()->after('employment_type_id')->constrained('employee_levels')->nullOnDelete();
            $table->foreignId('work_location_id')->nullable()->after('employee_level_id')->constrained('work_locations')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->after('work_location_id')->constrained('employees')->nullOnDelete();
            $table->date('probation_end_date')->nullable()->after('join_date');

            $table->foreign('province_id')->references('code')->on('provinces')->nullOnDelete();
            $table->foreign('city_id')->references('code')->on('cities')->nullOnDelete();
            $table->foreign('domicile_province_id')->references('code')->on('provinces')->nullOnDelete();
            $table->foreign('domicile_city_id')->references('code')->on('cities')->nullOnDelete();
            $table->index(['company_id', 'employee_level_id']);
            $table->index(['company_id', 'work_location_id']);
            $table->index(['company_id', 'supervisor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['domicile_province_id']);
            $table->dropForeign(['domicile_city_id']);
            $table->dropForeign(['religion_id']);
            $table->dropForeign(['marital_status_id']);
            $table->dropForeign(['blood_type_id']);
            $table->dropForeign(['employee_level_id']);
            $table->dropForeign(['work_location_id']);
            $table->dropForeign(['supervisor_id']);
            $table->dropIndex(['company_id', 'employee_level_id']);
            $table->dropIndex(['company_id', 'work_location_id']);
            $table->dropIndex(['company_id', 'supervisor_id']);
            $table->dropColumn([
                'nickname',
                'religion_id',
                'marital_status_id',
                'blood_type_id',
                'nationality',
                'passport_number',
                'province_id',
                'city_id',
                'domicile_address',
                'domicile_province_id',
                'domicile_city_id',
                'country_of_birth',
                'employee_level_id',
                'work_location_id',
                'supervisor_id',
                'probation_end_date',
            ]);
        });
    }
};
