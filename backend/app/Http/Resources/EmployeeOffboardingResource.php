<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeOffboardingResource extends JsonResource
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
            'reason_type' => $this->resource->getAttribute('reason_type'),
            'reason_detail' => $this->resource->getAttribute('reason_detail'),
            'last_working_date' => $this->resource->getAttribute('last_working_date'),
            'status' => $this->resource->getAttribute('status'),
            'initiated_by' => $this->resource->getAttribute('initiated_by'),
            'initiated_at' => $this->resource->getAttribute('initiated_at'),
            'completed_by' => $this->resource->getAttribute('completed_by'),
            'completed_at' => $this->resource->getAttribute('completed_at'),
            'notes' => $this->resource->getAttribute('notes'),
            'archived_at' => $this->resource->getAttribute('archived_at'),
            'archived_by' => $this->resource->getAttribute('archived_by'),
            'created_by' => $this->resource->getAttribute('created_by'),
            'updated_by' => $this->resource->getAttribute('updated_by'),
            'created_at' => $this->resource->getAttribute('created_at'),
            'updated_at' => $this->resource->getAttribute('updated_at'),
            'initiator' => $this->whenLoaded('initiator'),
            'completer' => $this->whenLoaded('completer'),
            'checklist_items' => $this->whenLoaded(
                'checklistItems',
                fn () => OffboardingChecklistItemResource::collection($this->resource->getRelation('checklistItems'))
            ),
        ];
    }
}
