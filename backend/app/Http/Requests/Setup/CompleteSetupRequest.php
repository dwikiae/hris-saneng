<?php

namespace App\Http\Requests\Setup;

use App\Models\Company;
use App\Models\User;
use App\Repositories\Contracts\InstanceSettingsRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Password;

class CompleteSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $settings = app(InstanceSettingsRepositoryInterface::class);

        return $settings->get('setup_completed') !== true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $requiresCompany = ! Company::query()->exists();
        $requiresAdmin = ! User::query()->withoutGlobalScope('company')->whereNull('company_id')->exists();

        return [
            'company' => [$requiresCompany ? 'required' : 'sometimes', 'array'],
            'company.name' => [$requiresCompany ? 'required' : 'sometimes', 'string', 'max:255'],
            'company.legal_name' => [$requiresCompany ? 'required' : 'sometimes', 'string', 'max:255'],
            'company.slug' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:companies,slug'],
            'company.timezone' => ['sometimes', 'string', 'max:100'],
            'company.date_format' => ['sometimes', 'string', 'max:30'],
            'company.language_default' => ['sometimes', 'string', 'in:id,en'],
            'admin' => [$requiresAdmin ? 'required' : 'sometimes', 'array'],
            'admin.name' => [$requiresAdmin ? 'required' : 'sometimes', 'string', 'max:255'],
            'admin.email' => [$requiresAdmin ? 'required' : 'sometimes', 'email', 'max:255', 'unique:users,email'],
            'admin.password' => [$requiresAdmin ? 'required' : 'sometimes', Password::min(8)->mixedCase()->numbers()],
            'admin.language_preference' => ['sometimes', 'string', 'in:id,en'],
        ];
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'setup.already_completed',
        ], 409));
    }
}
