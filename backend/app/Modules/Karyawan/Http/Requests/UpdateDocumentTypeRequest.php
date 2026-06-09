<?php

namespace App\Modules\Karyawan\Http\Requests;

class UpdateDocumentTypeRequest extends EmployeeMasterDataRequest
{
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', $this->uniqueCode('document_types')],
            'name' => ['required', 'string', 'max:255'],
            'is_mandatory' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
