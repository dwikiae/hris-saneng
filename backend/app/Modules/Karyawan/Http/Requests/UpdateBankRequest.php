<?php

namespace App\Modules\Karyawan\Http\Requests;

class UpdateBankRequest extends EmployeeMasterDataRequest
{
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', $this->uniqueCode('banks')],
            'name' => ['required', 'string', 'max:255'],
            'swift' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
