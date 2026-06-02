<?php

namespace App\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->resource;

        return [
            'stats' => $payload['stats'],
            'pending_approvals' => array_map(
                fn (array $item): array => $this->approvalItem($item),
                $payload['pending_approvals'] ?? []
            ),
            'recent_activities' => array_map(
                fn (array $item): array => $this->activityItem($item),
                $payload['recent_activities'] ?? []
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function approvalItem(array $item): array
    {
        return [
            'id' => $item['id'],
            'type' => $item['type'],
            'resource_id' => $item['resource_id'],
            'title' => $item['title'],
            'description' => $item['description'] ?? null,
            'requested_by' => $item['requested_by'] ?? null,
            'created_at' => $this->formatTimestamp($item['created_at'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function activityItem(array $item): array
    {
        return [
            'id' => $item['id'],
            'title' => $item['title'],
            'description' => $item['description'] ?? null,
            'actor' => $item['actor'] ?? null,
            'module' => $item['module'] ?? null,
            'created_at' => $this->formatTimestamp($item['created_at'] ?? null),
        ];
    }

    private function formatTimestamp(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toISOString();
        }

        return is_string($value) ? $value : null;
    }
}
