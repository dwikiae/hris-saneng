<?php

namespace App\Modules\Karyawan\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeModuleSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('karyawan.settings');
    }

    public function rules(): array
    {
        return [
            'employee_number_format' => ['sometimes', 'string', 'max:100'],
            'probation_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'contract_expiry_notify_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'pkwt_max_months' => ['sometimes', 'integer', 'min:1', 'max:60'],
        ];
    }
}
