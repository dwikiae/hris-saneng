<?php

namespace App\Http\Requests\Employee;

use App\Models\EmployeeContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateEmployeeContractRequest extends FormRequest
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
            'contract_type' => ['sometimes', 'string', Rule::in([EmployeeContract::TYPE_PKWT, EmployeeContract::TYPE_PKWTT])],
            'contract_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
