<?php

namespace App\Modules\Karyawan\Http\Requests;

class StoreEducationLevelMasterRequest extends EmployeeMasterDataRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', $this->uniqueCode('education_levels')],
            'name' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
