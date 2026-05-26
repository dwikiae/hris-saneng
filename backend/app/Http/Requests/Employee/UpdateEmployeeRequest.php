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
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'address' => ['sometimes', 'string'],
            'birth_date' => ['sometimes', 'date'],
            'birth_place' => ['sometimes', 'string', 'max:255'],
            'gender' => ['sometimes', 'string', 'max:20'],
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'position_id' => ['sometimes', 'integer', 'exists:positions,id'],
            'employment_type_id' => ['sometimes', 'integer', 'exists:employment_types,id'],
            'join_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:join_date'],
            'nik' => ['sometimes', 'string', 'max:50'],
            'npwp' => ['sometimes', 'nullable', 'string', 'max:50'],
            'bank_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bank_account_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'consent_at' => ['sometimes', 'date'],
            'approver_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }
}
