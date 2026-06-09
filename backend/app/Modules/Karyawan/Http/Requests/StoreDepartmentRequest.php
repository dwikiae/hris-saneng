<?php

namespace App\Modules\Karyawan\Http\Requests;

class StoreDepartmentRequest extends EmployeeMasterDataRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', $this->uniqueCode('departments')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => $this->companyScopedExists('departments'),
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
