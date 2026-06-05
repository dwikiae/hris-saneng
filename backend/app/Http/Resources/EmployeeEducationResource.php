<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeEducationResource extends JsonResource
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
            'institution_name' => $this->resource->getAttribute('institution_name'),
            'education_level_id' => $this->resource->getAttribute('education_level_id'),
            'major' => $this->resource->getAttribute('major'),
            'start_year' => $this->resource->getAttribute('start_year'),
            'end_year' => $this->resource->getAttribute('end_year'),
            'gpa' => $this->resource->getAttribute('gpa'),
            'certificate_number' => $this->resource->getAttribute('certificate_number'),
            'archived_at' => $this->resource->getAttribute('archived_at'),
            'archived_by' => $this->resource->getAttribute('archived_by'),
            'created_by' => $this->resource->getAttribute('created_by'),
            'updated_by' => $this->resource->getAttribute('updated_by'),
            'created_at' => $this->resource->getAttribute('created_at'),
            'updated_at' => $this->resource->getAttribute('updated_at'),
            'education_level' => $this->whenLoaded('educationLevel'),
        ];
    }
}
