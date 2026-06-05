<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeContractResource extends JsonResource
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
            'contract_type' => $this->resource->getAttribute('contract_type'),
            'contract_number' => $this->resource->getAttribute('contract_number'),
            'start_date' => $this->resource->getAttribute('start_date'),
            'end_date' => $this->resource->getAttribute('end_date'),
            'status' => $this->resource->getAttribute('status'),
            'notes' => $this->resource->getAttribute('notes'),
            'approved_by' => $this->resource->getAttribute('approved_by'),
            'approved_at' => $this->resource->getAttribute('approved_at'),
            'archived_at' => $this->resource->getAttribute('archived_at'),
            'archived_by' => $this->resource->getAttribute('archived_by'),
            'created_by' => $this->resource->getAttribute('created_by'),
            'updated_by' => $this->resource->getAttribute('updated_by'),
            'created_at' => $this->resource->getAttribute('created_at'),
            'updated_at' => $this->resource->getAttribute('updated_at'),
            'approver' => $this->whenLoaded('approver'),
        ];
    }
}
