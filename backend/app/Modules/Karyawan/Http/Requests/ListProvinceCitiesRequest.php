<?php

namespace App\Modules\Karyawan\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProvinceCitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => $this->route('code')]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:2', Rule::exists('provinces', 'code')],
        ];
    }
}
