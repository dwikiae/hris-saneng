<?php

namespace App\Http\Requests\Instance;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ExportInstanceAuditRequest extends FormRequest
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
            'format' => ['required', 'string', 'in:csv,xlsx'],
            'filename' => ['nullable', 'string', 'max:120'],
            'filters' => ['nullable', 'array'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'causer_id' => ['nullable', 'integer', 'min:1'],
            'log_name' => ['nullable', 'string', 'max:120'],
            'event' => ['nullable', 'string', 'max:120'],
            'actor' => ['nullable', 'string', 'max:255'],
            'module' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:120'],
            'search' => ['nullable', 'string', 'max:255'],
            'filters.date_from' => ['nullable', 'date'],
            'filters.date_to' => ['nullable', 'date'],
            'filters.causer_id' => ['nullable', 'integer', 'min:1'],
            'filters.log_name' => ['nullable', 'string', 'max:120'],
            'filters.event' => ['nullable', 'string', 'max:120'],
            'filters.actor' => ['nullable', 'string', 'max:255'],
            'filters.module' => ['nullable', 'string', 'max:120'],
            'filters.action' => ['nullable', 'string', 'max:120'],
            'filters.search' => ['nullable', 'string', 'max:255'],
        ];
    }
}
