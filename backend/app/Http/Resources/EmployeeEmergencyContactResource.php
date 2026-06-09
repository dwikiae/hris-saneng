<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeEmergencyContactResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'company_id' => $this->resource->getAttribute('company_id'),
            'employee_id' => $this->resource->getAttribute('employee_id'),
            'name' => $this->resource->getAttribute('name'),
            'relationship' => $this->resource->getAttribute('relationship'),
            'phone' => $this->resource->getAttribute('phone'),
            'archived_at' => $this->resource->getAttribute('archived_at'),
            'archived_by' => $this->resource->getAttribute('archived_by'),
            'created_by' => $this->resource->getAttribute('created_by'),
            'updated_by' => $this->resource->getAttribute('updated_by'),
            'created_at' => $this->resource->getAttribute('created_at'),
            'updated_at' => $this->resource->getAttribute('updated_at'),
        ];
    }
}
