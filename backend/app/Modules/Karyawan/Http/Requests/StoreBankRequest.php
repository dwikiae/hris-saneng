<?php

namespace App\Modules\Karyawan\Http\Requests;

class StoreBankRequest extends EmployeeMasterDataRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', $this->uniqueCode('banks')],
            'name' => ['required', 'string', 'max:255'],
            'swift' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
