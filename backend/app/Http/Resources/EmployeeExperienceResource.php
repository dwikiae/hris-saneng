<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeExperienceResource extends JsonResource
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
            'company_name' => $this->resource->getAttribute('company_name'),
            'position' => $this->resource->getAttribute('position'),
            'start_date' => $this->resource->getAttribute('start_date'),
            'end_date' => $this->resource->getAttribute('end_date'),
            'is_current' => $this->resource->getAttribute('is_current'),
            'responsibilities' => $this->resource->getAttribute('responsibilities'),
            'reason_leaving' => $this->resource->getAttribute('reason_leaving'),
            'archived_at' => $this->resource->getAttribute('archived_at'),
            'archived_by' => $this->resource->getAttribute('archived_by'),
            'created_by' => $this->resource->getAttribute('created_by'),
            'updated_by' => $this->resource->getAttribute('updated_by'),
            'created_at' => $this->resource->getAttribute('created_at'),
            'updated_at' => $this->resource->getAttribute('updated_at'),
        ];
    }
}
