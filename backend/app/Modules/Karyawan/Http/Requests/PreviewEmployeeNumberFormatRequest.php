<?php

namespace App\Modules\Karyawan\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class PreviewEmployeeNumberFormatRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('karyawan.settings');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'format' => ['nullable', 'string', 'max:100'],
        ];
    }
}
