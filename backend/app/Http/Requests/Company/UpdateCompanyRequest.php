<?php

namespace App\Http\Requests\Company;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $companyId = (int) $this->route('company');

        if (! $user instanceof User || ! Gate::allows('company.update')) {
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
            'name' => ['required', 'string', 'max:255', Rule::unique('companies', 'name')->ignore($companyId)],
            'legal_name' => ['required', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'date_format' => ['nullable', 'string', 'max:30'],
            'language_default' => ['nullable', 'string', Rule::in(['id', 'en'])],
        ];
    }
}
