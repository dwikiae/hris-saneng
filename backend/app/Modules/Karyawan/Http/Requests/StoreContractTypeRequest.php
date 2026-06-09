<?php

namespace App\Modules\Karyawan\Http\Requests;

use App\Modules\Karyawan\Models\ContractType;
use Illuminate\Validation\Rule;

class StoreContractTypeRequest extends EmployeeMasterDataRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', $this->uniqueCode('contract_types')],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in([ContractType::TYPE_PKWT, ContractType::TYPE_PKWTT])],
            'description' => ['nullable', 'string'],
            'max_duration_months' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
