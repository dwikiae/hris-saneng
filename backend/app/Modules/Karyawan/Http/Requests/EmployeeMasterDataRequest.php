<?php

namespace App\Modules\Karyawan\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

abstract class EmployeeMasterDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('karyawan.settings');
    }

    protected function companyId(): int
    {
        return (int) $this->attributes->get('company_id');
    }

    protected function uniqueCode(string $table): Unique
    {
        $rule = Rule::unique($table, 'code')->where('company_id', $this->companyId());
        $id = $this->route('id');

        return $id === null ? $rule : $rule->ignore((int) $id);
    }

    /**
     * @return array<int, mixed>
     */
    protected function companyScopedExists(string $table): array
    {
        return ['nullable', 'integer', Rule::exists($table, 'id')->where('company_id', $this->companyId())];
    }
}
