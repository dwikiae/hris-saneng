<?php

namespace App\Http\Requests\Employee;

use App\Models\EmployeeOffboarding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreEmployeeOffboardingRequest extends FormRequest
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
            'reason_type' => ['required', 'string', Rule::in([
                EmployeeOffboarding::REASON_RESIGNATION,
                EmployeeOffboarding::REASON_TERMINATION,
                EmployeeOffboarding::REASON_CONTRACT_END,
                EmployeeOffboarding::REASON_RETIREMENT,
                EmployeeOffboarding::REASON_OTHER,
            ])],
            'reason_detail' => ['nullable', 'string'],
            'last_working_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
