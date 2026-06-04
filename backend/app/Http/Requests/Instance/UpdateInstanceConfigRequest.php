<?php

namespace App\Http\Requests\Instance;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInstanceConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->isInstanceAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'timezone' => ['sometimes', 'string', 'timezone'],
            'defaultLanguage' => ['sometimes', 'string', Rule::in(['id', 'en'])],
            'dateFormat' => ['sometimes', 'string', Rule::in(['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD'])],
            'sessionDurationHours' => ['sometimes', 'integer', 'min:1', 'max:168'],
            'autoLogoutExpired' => ['sometimes', 'boolean'],
            'loginLockoutAttempts' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'loginLockoutMinutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'smtpHost' => ['sometimes', 'nullable', 'string', 'max:255'],
            'smtpPort' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'smtpEncryption' => ['sometimes', 'nullable', 'string', Rule::in(['tls', 'ssl', 'none'])],
            'smtpUsername' => ['sometimes', 'nullable', 'string', 'max:255'],
            'smtpPassword' => ['sometimes', 'nullable', 'string', 'max:255'],
            'smtpFromName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'smtpFromEmail' => ['sometimes', 'nullable', 'email', 'max:255'],
        ];
    }
}
