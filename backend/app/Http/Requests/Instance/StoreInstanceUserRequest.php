<?php

namespace App\Http\Requests\Instance;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstanceUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->isInstanceAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_id' => $this->input('employee_id', $this->input('employeeId')),
            'company_ids' => $this->input('company_ids', $this->input('companyIds', [])),
            'role_ids' => $this->input('role_ids', $this->input('roleIds', [])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'employee_id' => ['nullable', 'integer'],
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
