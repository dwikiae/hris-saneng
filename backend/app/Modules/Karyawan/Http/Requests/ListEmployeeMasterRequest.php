<?php

namespace App\Modules\Karyawan\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ListEmployeeMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('karyawan.settings');
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'department_id' => ['nullable', 'integer'],
        ];
    }
}
