<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeNoteResource extends JsonResource
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
            'content' => $this->resource->getAttribute('content'),
            'type' => $this->resource->getAttribute('type'),
            'mentioned_users' => $this->resource->getAttribute('mentioned_users'),
            'created_by' => $this->resource->getAttribute('created_by'),
            'created_at' => $this->resource->getAttribute('created_at'),
            'updated_at' => $this->resource->getAttribute('updated_at'),
            'created_by_user' => $this->whenLoaded('createdBy'),
        ];
    }
}
