<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class EmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->resource->getKey(),
            'company_id' => $this->resource->getAttribute('company_id'),
            'employee_number' => $this->resource->getAttribute('employee_number'),
            'name' => $this->resource->getAttribute('name'),
            'email' => $this->resource->getAttribute('email'),
            'phone' => $this->resource->getAttribute('phone'),
            'address' => $this->resource->getAttribute('address'),
            'birth_date' => $this->resource->getAttribute('birth_date'),
            'birth_place' => $this->resource->getAttribute('birth_place'),
            'gender' => $this->resource->getAttribute('gender'),
            'department_id' => $this->resource->getAttribute('department_id'),
            'position_id' => $this->resource->getAttribute('position_id'),
            'employment_type_id' => $this->resource->getAttribute('employment_type_id'),
            'join_date' => $this->resource->getAttribute('join_date'),
            'end_date' => $this->resource->getAttribute('end_date'),
            'bank_name' => $this->resource->getAttribute('bank_name'),
            'consent_at' => $this->resource->getAttribute('consent_at'),
            'consent_by' => $this->resource->getAttribute('consent_by'),
            'status' => $this->resource->getAttribute('status'),
            'approver_id' => $this->resource->getAttribute('approver_id'),
            'approved_by' => $this->resource->getAttribute('approved_by'),
            'approved_at' => $this->resource->getAttribute('approved_at'),
            'rejection_reason' => $this->resource->getAttribute('rejection_reason'),
            'archived_at' => $this->resource->getAttribute('archived_at'),
            'archived_by' => $this->resource->getAttribute('archived_by'),
            'created_by' => $this->resource->getAttribute('created_by'),
            'updated_by' => $this->resource->getAttribute('updated_by'),
            'created_at' => $this->resource->getAttribute('created_at'),
            'updated_at' => $this->resource->getAttribute('updated_at'),
            'department' => $this->whenLoaded('department'),
            'position' => $this->whenLoaded('position'),
            'user' => $this->whenLoaded('user'),
        ];

        if ($this->canViewIdentityFields($request)) {
            $data['nik'] = $this->resource->getAttribute('nik');
            $data['npwp'] = $this->resource->getAttribute('npwp');
            $data['bank_account_number'] = $this->resource->getAttribute('bank_account_number');
        }

        if (Gate::check('employee.view_salary')) {
            $data['salary'] = $this->resource->getAttribute('salary');
            $data['allowances'] = $this->resource->getAttribute('allowances');
            $data['deductions'] = $this->resource->getAttribute('deductions');
        }

        return $data;
    }

    private function canViewIdentityFields(Request $request): bool
    {
        if (! Gate::check('employee.view')) {
            return false;
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        $userEmployeeId = $user->getAttribute('employee_id');

        if ($userEmployeeId === null) {
            return true;
        }

        return (int) $userEmployeeId === (int) $this->resource->getKey();
    }

    private function isSystemAdmin(User $user): bool
    {
        return $user->roles()
            ->where('code', 'system_admin')
            ->exists();
    }
}
