<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'type' => $this->resource->getAttribute('type'),
            'title_key' => $this->resource->getAttribute('title_key'),
            'body_key' => $this->resource->getAttribute('body_key'),
            'data' => $this->resource->getAttribute('data') ?? [],
            'read_at' => $this->resource->getAttribute('read_at')?->toISOString(),
            'created_at' => $this->resource->getAttribute('created_at')?->toISOString(),
        ];
    }
}
