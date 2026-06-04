<?php

namespace App\Modules\Karyawan\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('karyawan.settings');
    }

    public function rules(): array
    {
        $companyId = (int) $this->attributes->get('company_id');
        $id = (int) $this->route('id');

        return [
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('work_locations', 'code')->where('company_id', $companyId)->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'city_id' => ['nullable', 'string', 'max:5', Rule::exists('cities', 'code')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
