<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, description: string, permissions: array<int, string>|string}>
     */
    private array $roles = [
        'system_admin' => [
            'name' => 'Platform Administrator',
            'description' => 'Full platform access',
            'permissions' => '*',
        ],
        'manager' => [
            'name' => 'Manager',
            'description' => 'Company manager access',
            'permissions' => ['company.view', 'company.update', 'user.view', 'user.create', 'user.update', 'user.assign_role'],
        ],
        'staff' => [
            'name' => 'Staff',
            'description' => 'Company staff access',
            'permissions' => ['company.view', 'user.view'],
        ],
        'hr_manager' => [
            'name' => 'HR Manager',
            'description' => 'Full HR and recruitment access',
            'permissions' => ['employee.*', 'recruitment.*'],
        ],
        'hr_staff' => [
            'name' => 'HR Staff',
            'description' => 'Limited HR operations access',
            'permissions' => ['employee.view', 'employee.create', 'recruitment.view'],
        ],
        'approver' => [
            'name' => 'Approver',
            'description' => 'Employee data approval access',
            'permissions' => ['employee.approve', 'employee.view'],
        ],
        'employee' => [
            'name' => 'Employee',
            'description' => 'Employee placeholder role',
            'permissions' => [],
        ],
    ];

    public function run(): void
    {
        $company = Company::query()->where('name', 'PT Saneng')->firstOrFail();

        foreach ($this->roles as $code => $definition) {
            $role = Role::withoutCompanyScope()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $code,
                ],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_active' => true,
                ]
            );

            $role->permissions()->sync($this->permissionIds($company->id, $definition['permissions']));
        }
    }

    /**
     * @param  array<int, string>|string  $permissions
     * @return array<int, int>
     */
    private function permissionIds(int $companyId, array|string $permissions): array
    {
        if ($permissions === []) {
            return [];
        }

        $query = Permission::withoutCompanyScope()->where('company_id', $companyId);

        if ($permissions === '*') {
            return $query->pluck('id')->all();
        }

        $exactCodes = array_filter($permissions, fn (string $permission): bool => ! str_ends_with($permission, '.*'));
        $modulePrefixes = array_map(
            fn (string $permission): string => substr($permission, 0, -2),
            array_filter($permissions, fn (string $permission): bool => str_ends_with($permission, '.*'))
        );

        return $query
            ->where(function ($query) use ($exactCodes, $modulePrefixes) {
                if ($exactCodes !== []) {
                    $query->whereIn('code', $exactCodes);
                }

                foreach ($modulePrefixes as $module) {
                    $method = $exactCodes === [] && $module === reset($modulePrefixes) ? 'where' : 'orWhere';
                    $query->{$method}('module', $module);
                }
            })
            ->pluck('id')
            ->all();
    }
}
