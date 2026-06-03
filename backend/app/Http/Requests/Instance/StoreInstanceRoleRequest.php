<?php

namespace App\Http\Requests\Instance;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreInstanceRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->isInstanceAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_id' => $this->input('company_id', $this->input('companyId')),
            'copy_from_role_id' => $this->input('copy_from_role_id', $this->input('copyFromRoleId')),
            'copy_from_job_position_id' => $this->input('copy_from_job_position_id', $this->input('copyFromJobPositionId')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'copy_from_role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'copy_from_job_position_id' => ['nullable', 'integer'],
        ];
    }
}
