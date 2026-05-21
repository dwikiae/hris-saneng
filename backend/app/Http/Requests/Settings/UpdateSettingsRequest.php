<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('settings.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password_min_length' => ['sometimes', 'integer', 'min:8', 'max:128'],
            'password_requires_uppercase' => ['sometimes', 'boolean'],
            'password_requires_number' => ['sometimes', 'boolean'],
            'password_requires_symbol' => ['sometimes', 'boolean'],
            'max_login_attempts' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'lockout_duration_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'session_lifetime_minutes' => ['sometimes', 'integer', 'min:5', 'max:1440'],
            'retention_employee_years' => ['sometimes', 'integer', 'min:5', 'max:100'],
            'retention_candidate_years' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'retention_audit_years' => ['sometimes', 'integer', 'min:2', 'max:100'],
            'smtp_host' => ['sometimes', 'nullable', 'string', 'max:255'],
            'smtp_port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'smtp_password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'smtp_from_address' => ['sometimes', 'nullable', 'email', 'max:255'],
            'smtp_from_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
