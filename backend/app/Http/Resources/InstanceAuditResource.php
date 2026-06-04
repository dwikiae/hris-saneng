<?php

namespace App\Http\Resources;

use App\Application\Instance\InstanceAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstanceAuditResource extends JsonResource
{
    public function __construct(mixed $resource, private readonly bool $canViewSensitive)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return app(InstanceAuditService::class)
            ->transform($this->resource, $this->canViewSensitive);
    }
}
