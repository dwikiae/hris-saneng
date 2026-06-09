<?php

namespace App\Modules\Karyawan\Http\Requests;

class StoreReligionRequest extends EmployeeMasterDataRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', $this->uniqueCode('religions')],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
