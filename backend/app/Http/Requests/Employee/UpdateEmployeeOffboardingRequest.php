<?php

namespace App\Http\Requests\Employee;

use App\Models\EmployeeOffboarding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateEmployeeOffboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('employee.archive');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason_type' => ['sometimes', 'string', Rule::in([
                EmployeeOffboarding::REASON_RESIGNATION,
                EmployeeOffboarding::REASON_TERMINATION,
                EmployeeOffboarding::REASON_CONTRACT_END,
                EmployeeOffboarding::REASON_RETIREMENT,
                EmployeeOffboarding::REASON_OTHER,
            ])],
            'reason_detail' => ['sometimes', 'nullable', 'string'],
            'last_working_date' => ['sometimes', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
