<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateEmployeeEducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('employee.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'institution_name' => ['sometimes', 'string', 'max:255'],
            'education_level_id' => ['sometimes', 'integer', 'exists:education_levels,id'],
            'major' => ['sometimes', 'nullable', 'string', 'max:255'],
            'start_year' => ['sometimes', 'integer', 'min:1900', 'max:2100'],
            'end_year' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:2100', 'gte:start_year'],
            'gpa' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:4'],
            'certificate_number' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
