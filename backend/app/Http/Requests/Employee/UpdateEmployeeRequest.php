<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_number' => ['sometimes', 'string', 'max:50'],
            'name' => ['sometimes', 'string', 'max:255'],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'address' => ['sometimes', 'string'],
            'province_id' => ['sometimes', 'nullable', 'string', 'size:2', 'exists:provinces,code'],
            'city_id' => ['sometimes', 'nullable', 'string', 'size:5', 'exists:cities,code'],
            'domicile_address' => ['sometimes', 'nullable', 'string'],
            'domicile_province_id' => ['sometimes', 'nullable', 'string', 'size:2', 'exists:provinces,code'],
            'domicile_city_id' => ['sometimes', 'nullable', 'string', 'size:5', 'exists:cities,code'],
            'birth_date' => ['sometimes', 'date'],
            'birth_place' => ['sometimes', 'string', 'max:255'],
            'country_of_birth' => ['sometimes', 'nullable', 'string', 'max:255'],
            'gender' => ['sometimes', 'string', 'max:20'],
            'religion_id' => ['sometimes', 'nullable', 'integer', 'exists:religions,id'],
            'marital_status_id' => ['sometimes', 'nullable', 'integer', 'exists:marital_statuses,id'],
            'blood_type_id' => ['sometimes', 'nullable', 'integer', 'exists:blood_types,id'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:255'],
            'passport_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'position_id' => ['sometimes', 'integer', 'exists:positions,id'],
            'employment_type_id' => ['sometimes', 'integer', 'exists:employment_types,id'],
            'employee_level_id' => ['sometimes', 'nullable', 'integer', 'exists:employee_levels,id'],
            'work_location_id' => ['sometimes', 'nullable', 'integer', 'exists:work_locations,id'],
            'supervisor_id' => ['sometimes', 'nullable', 'integer', 'exists:employees,id'],
            'join_date' => ['sometimes', 'date'],
            'probation_end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:join_date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:join_date'],
            'nik' => ['sometimes', 'string', 'max:50'],
            'npwp' => ['sometimes', 'nullable', 'string', 'max:50'],
            'bank_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bank_account_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'salary' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'allowances' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'deductions' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'consent_at' => ['sometimes', 'date'],
            'approver_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }
}
