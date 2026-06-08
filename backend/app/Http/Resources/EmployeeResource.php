<?php

namespace App\Http\Resources;

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
            'nickname' => $this->resource->getAttribute('nickname'),
            'email' => $this->resource->getAttribute('email'),
            'phone' => $this->resource->getAttribute('phone'),
            'address' => $this->resource->getAttribute('address'),
            'province_id' => $this->resource->getAttribute('province_id'),
            'city_id' => $this->resource->getAttribute('city_id'),
            'domicile_address' => $this->resource->getAttribute('domicile_address'),
            'domicile_province_id' => $this->resource->getAttribute('domicile_province_id'),
            'domicile_city_id' => $this->resource->getAttribute('domicile_city_id'),
            'birth_date' => $this->resource->getAttribute('birth_date'),
            'birth_place' => $this->resource->getAttribute('birth_place'),
            'country_of_birth' => $this->resource->getAttribute('country_of_birth'),
            'gender' => $this->resource->getAttribute('gender'),
            'religion_id' => $this->resource->getAttribute('religion_id'),
            'marital_status_id' => $this->resource->getAttribute('marital_status_id'),
            'blood_type_id' => $this->resource->getAttribute('blood_type_id'),
            'nationality' => $this->resource->getAttribute('nationality'),
            'department_id' => $this->resource->getAttribute('department_id'),
            'position_id' => $this->resource->getAttribute('position_id'),
            'employment_type_id' => $this->resource->getAttribute('employment_type_id'),
            'employee_level_id' => $this->resource->getAttribute('employee_level_id'),
            'work_location_id' => $this->resource->getAttribute('work_location_id'),
            'supervisor_id' => $this->resource->getAttribute('supervisor_id'),
            'join_date' => $this->resource->getAttribute('join_date'),
            'probation_end_date' => $this->resource->getAttribute('probation_end_date'),
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
            'employment_type' => $this->whenLoaded('employmentType'),
            'religion' => $this->whenLoaded('religion'),
            'marital_status' => $this->whenLoaded('maritalStatus'),
            'blood_type' => $this->whenLoaded('bloodType'),
            'province' => $this->whenLoaded('province'),
            'city' => $this->whenLoaded('city'),
            'domicile_province' => $this->whenLoaded('domicileProvince'),
            'domicile_city' => $this->whenLoaded('domicileCity'),
            'employee_level' => $this->whenLoaded('employeeLevel'),
            'work_location' => $this->whenLoaded('workLocation'),
            'supervisor' => $this->whenLoaded('supervisor'),
            'user' => $this->whenLoaded('user'),
        ];

        if ($this->canViewSensitiveFields()) {
            $data['nik'] = $this->resource->getAttribute('nik');
            $data['npwp'] = $this->resource->getAttribute('npwp');
            $data['passport_number'] = $this->resource->getAttribute('passport_number');
            $data['bank_account_number'] = $this->resource->getAttribute('bank_account_number');
            $data['bank_account_holder_name'] = $this->resource->getAttribute('bank_account_holder_name');
        }

        if (Gate::check('employee.view_salary')) {
            $data['salary'] = $this->resource->getAttribute('salary');
            $data['allowances'] = $this->resource->getAttribute('allowances');
            $data['deductions'] = $this->resource->getAttribute('deductions');
        }

        return $data;
    }

    private function canViewSensitiveFields(): bool
    {
        return Gate::check('employee.view_sensitive');
    }
}
