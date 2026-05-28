<?php

namespace App\Http\Requests\Company;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreCompanyUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $companyId = (int) $this->route('company');

        if (! $user instanceof User || ! Gate::allows('user.create')) {
            return false;
        }

        return $user->isInstanceAdmin() || (int) $user->company_id === $companyId;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = (int) $this->route('company');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'language_preference' => ['nullable', 'string', Rule::in(['id', 'en'])],
            'employee_id' => ['nullable', 'integer', Rule::unique('users', 'employee_id')],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => [
                'integer',
                Rule::exists('roles', 'id')->where('company_id', $companyId),
            ],
        ];
    }
}
