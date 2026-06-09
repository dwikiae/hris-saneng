<?php

namespace App\Modules\Karyawan\Http\Requests;

class StoreJobPositionRequest extends EmployeeMasterDataRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', $this->uniqueCode('positions')],
            'name' => ['required', 'string', 'max:255'],
            'department_id' => $this->companyScopedExists('departments'),
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
