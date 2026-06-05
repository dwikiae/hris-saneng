<?php

namespace App\Http\Requests\Employee;

use App\Models\EmployeeContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreEmployeeContractRequest extends FormRequest
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
            'contract_type' => ['required', 'string', Rule::in([EmployeeContract::TYPE_PKWT, EmployeeContract::TYPE_PKWTT])],
            'contract_number' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
