<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OffboardingChecklistItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'company_id' => $this->resource->getAttribute('company_id'),
            'offboarding_id' => $this->resource->getAttribute('offboarding_id'),
            'title' => $this->resource->getAttribute('title'),
            'description' => $this->resource->getAttribute('description'),
            'assigned_to' => $this->resource->getAttribute('assigned_to'),
            'is_completed' => $this->resource->getAttribute('is_completed'),
            'completed_by' => $this->resource->getAttribute('completed_by'),
            'completed_at' => $this->resource->getAttribute('completed_at'),
            'due_date' => $this->resource->getAttribute('due_date'),
            'order' => $this->resource->getAttribute('order'),
            'archived_at' => $this->resource->getAttribute('archived_at'),
            'archived_by' => $this->resource->getAttribute('archived_by'),
            'created_by' => $this->resource->getAttribute('created_by'),
            'updated_by' => $this->resource->getAttribute('updated_by'),
            'created_at' => $this->resource->getAttribute('created_at'),
            'updated_at' => $this->resource->getAttribute('updated_at'),
            'assignee' => $this->whenLoaded('assignee'),
            'completer' => $this->whenLoaded('completer'),
        ];
    }
}
