<?php

namespace App\Http\Requests\Instance;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstanceCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->isInstanceAdmin();
    }

    protected function prepareForValidation(): void
    {
        $aliases = [
            'legal_name' => 'legalName',
            'logo_path' => 'logoPath',
            'date_format' => 'dateFormat',
            'language_default' => 'languageDefault',
        ];
        $payload = [];

        foreach ($aliases as $snake => $camel) {
            if ($this->has($snake) || $this->has($camel)) {
                $payload[$snake] = $this->input($snake, $this->input($camel));
            }
        }

        $this->merge($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('companies', 'name')],
            'legal_name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('companies', 'slug')],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseRules(): array
    {
        return [
            'npwp' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'timezone' => ['sometimes', 'string', 'max:100'],
            'date_format' => ['sometimes', 'string', 'max:30'],
            'language_default' => ['sometimes', 'string', Rule::in(['id', 'en'])],
            'tagline' => ['nullable', 'string', 'max:255'],
            'companyType' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'foundedDate' => ['nullable', 'date'],
            'province' => ['nullable', 'string', 'max:100'],
            'postalCode' => ['nullable', 'string', 'max:30'],
            'hrPicName' => ['nullable', 'string', 'max:255'],
            'hrPicPhone' => ['nullable', 'string', 'max:50'],
            'hrPicEmail' => ['nullable', 'email', 'max:255'],
            'nibOrSiup' => ['nullable', 'string', 'max:100'],
            'bpjsKetenagakerjaan' => ['nullable', 'string', 'max:100'],
            'bpjsKesehatan' => ['nullable', 'string', 'max:100'],
            'wlkpNumber' => ['nullable', 'string', 'max:100'],
            'directorName' => ['nullable', 'string', 'max:255'],
            'smtpHost' => ['nullable', 'string', 'max:255'],
            'smtpPort' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtpUsername' => ['nullable', 'string', 'max:255'],
            'smtpPasswordMasked' => ['nullable', 'string', 'max:255'],
            'smtpFromName' => ['nullable', 'string', 'max:255'],
            'smtpFromEmail' => ['nullable', 'email', 'max:255'],
            'loginLockoutAttempts' => ['nullable', 'integer', 'min:1', 'max:20'],
            'loginLockoutMinutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'sessionDurationHours' => ['nullable', 'integer', 'min:1', 'max:168'],
            'activeModuleCodes' => ['nullable', 'array'],
            'activeModuleCodes.*' => ['string', 'max:100'],
        ];
    }
}
