<?php

namespace App\Modules\Karyawan\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends EmployeeMasterDataRequest
{
    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'code' => ['sometimes', 'string', 'max:50', $this->uniqueCode('departments')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => [...$this->companyScopedExists('departments'), Rule::notIn([$id])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
