<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
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
            'employee_number' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string'],
            'province_id' => ['nullable', 'string', 'size:2', 'exists:provinces,code'],
            'city_id' => ['nullable', 'string', 'size:5', 'exists:cities,code'],
            'domicile_address' => ['nullable', 'string'],
            'domicile_province_id' => ['nullable', 'string', 'size:2', 'exists:provinces,code'],
            'domicile_city_id' => ['nullable', 'string', 'size:5', 'exists:cities,code'],
            'birth_date' => ['required', 'date'],
            'birth_place' => ['required', 'string', 'max:255'],
            'country_of_birth' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:20'],
            'religion_id' => ['nullable', 'integer', 'exists:religions,id'],
            'marital_status_id' => ['nullable', 'integer', 'exists:marital_statuses,id'],
            'blood_type_id' => ['nullable', 'integer', 'exists:blood_types,id'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'passport_number' => ['nullable', 'string', 'max:100'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'employment_type_id' => ['required', 'integer', 'exists:employment_types,id'],
            'employee_level_id' => ['nullable', 'integer', 'exists:employee_levels,id'],
            'work_location_id' => ['nullable', 'integer', 'exists:work_locations,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:employees,id'],
            'join_date' => ['required', 'date'],
            'probation_end_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'nik' => ['required', 'string', 'max:50'],
            'npwp' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'consent_at' => ['required', 'date'],
            'approver_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
