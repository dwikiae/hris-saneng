<?php

namespace App\Application\Instance;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class PlatformAdministratorAssignmentService
{
    public const ROLE_CODE = 'system_admin';
    public const ROLE_NAME = 'Platform Administrator';

    public function assignAllToCompany(Company $company): void
    {
        $role = $this->roleForCompany($company);

        $this->platformAdministrators()
            ->each(fn (User $user) => $this->attachRole($user, $role));
    }

    public function assignUserToCompany(User $user, Company $company): void
    {
        $this->attachRole($user, $this->roleForCompany($company));
    }

    public function assignUserToAllCompanies(User $user): void
    {
        Company::query()
            ->orderBy('id')
            ->each(fn (Company $company) => $this->assignUserToCompany($user, $company));
    }

    private function roleForCompany(Company $company): Role
    {
        $companyId = (int) $company->getKey();
        $role = Role::withoutCompanyScope()->updateOrCreate(
            [
                'company_id' => $companyId,
                'code' => self::ROLE_CODE,
            ],
            [
                'name' => self::ROLE_NAME,
                'description' => 'Full platform access',
                'is_active' => true,
            ]
        );

        $role->permissions()->sync($this->permissionIds($companyId));

        return $role;
    }

    /**
     * @return array<int, int>
     */
    private function permissionIds(int $companyId): array
    {
        return Permission::withoutCompanyScope()
            ->where('company_id', $companyId)
            ->pluck('id')
            ->all();
    }

    /**
     * @return Collection<int, User>
     */
    private function platformAdministrators(): Collection
    {
        return User::withoutCompanyScope()
            ->whereNull('company_id')
            ->orderBy('id')
            ->get();
    }

    private function attachRole(User $user, Role $role): void
    {
        $user->roles()->syncWithoutDetaching([(int) $role->getKey()]);
    }
}
