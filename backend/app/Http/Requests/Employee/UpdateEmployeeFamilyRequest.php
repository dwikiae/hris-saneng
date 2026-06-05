<?php

namespace App\Http\Requests\Employee;

use App\Models\EmployeeFamily;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateEmployeeFamilyRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'relationship' => ['sometimes', 'string', Rule::in([
                EmployeeFamily::RELATIONSHIP_SPOUSE,
                EmployeeFamily::RELATIONSHIP_CHILD,
                EmployeeFamily::RELATIONSHIP_PARENT,
                EmployeeFamily::RELATIONSHIP_SIBLING,
                EmployeeFamily::RELATIONSHIP_OTHER,
            ])],
            'birth_date' => ['sometimes', 'nullable', 'date'],
            'gender' => ['sometimes', 'string', 'max:20'],
            'occupation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_dependent' => ['sometimes', 'boolean'],
        ];
    }
}
