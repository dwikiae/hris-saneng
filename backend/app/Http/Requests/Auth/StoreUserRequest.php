<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password'            => ['required', 'string', 'min:8'],
            'language_preference' => ['nullable', 'string', Rule::in(['id', 'en'])],
            'employee_id'         => ['nullable', 'integer', Rule::unique('users', 'employee_id')],
        ];
    }
}
