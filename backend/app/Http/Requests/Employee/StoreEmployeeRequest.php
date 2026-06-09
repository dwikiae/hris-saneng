<?php

namespace App\Http\Requests\Employee;

use App\Models\EmployeeContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'employee_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
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
            'employment_type_id' => ['nullable', 'integer', 'exists:employment_types,id'],
            'contract_type' => ['nullable', 'string', Rule::in([EmployeeContract::TYPE_PKWT, EmployeeContract::TYPE_PKWTT])],
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
            'bank_account_holder_name' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'consent_at' => ['nullable', 'date'],
            'approver_id' => ['nullable', 'integer', 'exists:users,id'],
            'contract' => ['nullable', 'array'],
            'contract.contract_type' => ['required_with:contract', 'string', Rule::in([EmployeeContract::TYPE_PKWT, EmployeeContract::TYPE_PKWTT])],
            'contract.contract_number' => ['nullable', 'string', 'max:255'],
            'contract.start_date' => ['required_with:contract', 'date'],
            'contract.end_date' => ['nullable', 'date', 'after_or_equal:contract.start_date'],
            'contract.notes' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'array'],
            'emergency_contact.name' => ['required_with:emergency_contact', 'string', 'max:255'],
            'emergency_contact.relationship' => ['nullable', 'string', 'max:255'],
            'emergency_contact.phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
