<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstanceRoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $company = $this->resource->relationLoaded('company') ? $this->resource->getRelation('company') : null;

        return [
            'id' => $this->resource->getAttribute('id'),
            'name' => $this->resource->getAttribute('name'),
            'code' => $this->resource->getAttribute('code'),
            'description' => $this->resource->getAttribute('description'),
            'company' => $company === null ? null : [
                'id' => $company->getAttribute('id'),
                'name' => $company->getAttribute('name'),
            ],
            'userCount' => $this->resource->getAttribute('users_count'),
            'permissionCount' => $this->resource->getAttribute('permissions_count'),
            'status' => $this->resource->getAttribute('archived_at') === null ? 'active' : 'archived',
            'users' => $this->users(),
            'permissionIds' => $this->permissionIds(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function users(): array
    {
        if (! $this->resource->relationLoaded('users')) {
            return [];
        }

        return $this->resource->getRelation('users')
            ->map(fn (User $user): array => [
                'id' => $user->getAttribute('id'),
                'name' => $user->getAttribute('name'),
                'email' => $user->getAttribute('email'),
                'status' => (bool) $user->getAttribute('force_password_reset') ? 'pending' : 'active',
                'roles' => [],
                'companies' => [],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function permissionIds(): array
    {
        if (! $this->resource->relationLoaded('permissions')) {
            return [];
        }

        return $this->resource->getRelation('permissions')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }
}
