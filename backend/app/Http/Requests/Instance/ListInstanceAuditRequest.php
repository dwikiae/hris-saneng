<?php

namespace App\Http\Requests\Instance;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ListInstanceAuditRequest extends FormRequest
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
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'causer_id' => ['nullable', 'integer', 'min:1'],
            'log_name' => ['nullable', 'string', 'max:120'],
            'event' => ['nullable', 'string', 'max:120'],
            'actor' => ['nullable', 'string', 'max:255'],
            'module' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:120'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
