<?php

namespace App\Modules\Karyawan\Http\Requests;

class UpdateJobPositionRequest extends EmployeeMasterDataRequest
{
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', $this->uniqueCode('positions')],
            'name' => ['required', 'string', 'max:255'],
            'department_id' => $this->companyScopedExists('departments'),
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
