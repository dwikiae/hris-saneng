<?php

namespace App\Http\Resources;

use App\Models\Role;
use App\Models\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class InstanceUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roles = $this->roles();
        $companies = $roles
            ->map(fn (Role $role): mixed => $role->getRelation('company'))
            ->filter()
            ->map(fn ($company): array => [
                'id' => $company->getAttribute('id'),
                'name' => $company->getAttribute('name'),
            ])
            ->unique('id')
            ->values();

        if ($companies->isEmpty() && $this->resource->relationLoaded('company') && $this->resource->getRelation('company') !== null) {
            $company = $this->resource->getRelation('company');
            $companies->push([
                'id' => $company->getAttribute('id'),
                'name' => $company->getAttribute('name'),
            ]);
        }

        return [
            'id' => $this->resource->getAttribute('id'),
            'name' => $this->resource->getAttribute('name'),
            'email' => $this->resource->getAttribute('email'),
            'avatarUrl' => null,
            'employeeId' => $this->resource->getAttribute('employee_id'),
            'employeeName' => null,
            'companies' => $companies->all(),
            'roles' => $roles->map(fn (Role $role): array => [
                'id' => $role->getAttribute('id'),
                'name' => $role->getAttribute('name'),
                'companyId' => $role->getAttribute('company_id'),
                'companyName' => $role->relationLoaded('company') && $role->getRelation('company') !== null
                    ? $role->getRelation('company')->getAttribute('name')
                    : null,
            ])->values()->all(),
            'status' => $this->status(),
            'invitationStatus' => $this->invitationStatus(),
            'lastLoginAt' => $this->resource->getAttribute('last_login_at'),
            'lastActiveAt' => $this->resource->getAttribute('last_login_at'),
            'loginHistory' => [],
        ];
    }

    /**
     * @return Collection<int, Role>
     */
    private function roles(): Collection
    {
        if (! $this->resource->relationLoaded('roles')) {
            return collect();
        }

        return $this->resource->getRelation('roles');
    }

    private function status(): string
    {
        if ($this->resource->getAttribute('archived_at') !== null) {
            return 'archived';
        }

        return (bool) $this->resource->getAttribute('force_password_reset') ? 'pending' : 'active';
    }

    private function invitationStatus(): ?string
    {
        if (! $this->resource->relationLoaded('invitations')) {
            return null;
        }

        /** @var UserInvitation|null $invitation */
        $invitation = $this->resource->getRelation('invitations')->first();

        if (! $invitation instanceof UserInvitation) {
            return null;
        }

        if ($invitation->accepted_at !== null) {
            return 'accepted';
        }

        return $invitation->expires_at->isPast() ? 'expired' : 'pending';
    }
}
