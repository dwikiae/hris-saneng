<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getAttribute('id'),
            'company_id' => $this->resource->getAttribute('company_id'),
            'name' => $this->resource->getAttribute('name'),
            'email' => $this->resource->getAttribute('email'),
            'employee_id' => $this->resource->getAttribute('employee_id'),
            'language_preference' => $this->resource->getAttribute('language_preference'),
            'force_password_reset' => $this->resource->getAttribute('force_password_reset'),
            'last_login_at' => $this->resource->getAttribute('last_login_at'),
            'archived_at' => $this->resource->getAttribute('archived_at'),
            'roles' => $this->whenLoaded('roles', fn (): mixed => $this->resource->roles->map(fn ($role): array => [
                'id' => $role->getAttribute('id'),
                'code' => $role->getAttribute('code'),
                'name' => $role->getAttribute('name'),
            ])->values()),
        ];
    }
}
