<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * @param  array<int, string>  $codes
 */
function grantTestPermissions(Role $role, Company $company, array $codes): void
{
    foreach ($codes as $code) {
        [$module, $action] = explode('.', $code, 2);
        $permission = Permission::withoutCompanyScope()->firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => $code,
            ],
            [
                'module' => $module,
                'action' => $action,
                'name' => $code,
            ]
        );

        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }
}
