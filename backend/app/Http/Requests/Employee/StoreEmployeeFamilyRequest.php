<?php

namespace App\Http\Requests\Employee;

use App\Models\EmployeeFamily;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreEmployeeFamilyRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', Rule::in([
                EmployeeFamily::RELATIONSHIP_SPOUSE,
                EmployeeFamily::RELATIONSHIP_CHILD,
                EmployeeFamily::RELATIONSHIP_PARENT,
                EmployeeFamily::RELATIONSHIP_SIBLING,
                EmployeeFamily::RELATIONSHIP_OTHER,
            ])],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['required', 'string', 'max:20'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_dependent' => ['sometimes', 'boolean'],
        ];
    }
}
