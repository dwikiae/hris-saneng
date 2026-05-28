<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateInstanceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->isInstanceAdmin()
            && Gate::allows('settings.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'default_locale' => ['sometimes', 'string', 'in:id,en'],
            'storage_driver' => ['sometimes', 'nullable', 'string', 'max:50'],
            'storage_endpoint' => ['sometimes', 'nullable', 'string', 'max:255'],
            'smtp_host' => ['sometimes', 'nullable', 'string', 'max:255'],
            'smtp_port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
