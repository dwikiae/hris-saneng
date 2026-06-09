<?php

namespace App\Modules\Karyawan\Http\Requests;

class UpdateReligionRequest extends EmployeeMasterDataRequest
{
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', $this->uniqueCode('religions')],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
