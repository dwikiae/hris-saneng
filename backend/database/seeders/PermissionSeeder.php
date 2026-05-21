<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string, description: string}>
     */
    private array $permissions = [
        ['code' => 'employee.view', 'name' => 'View Employee', 'description' => 'View employee records'],
        ['code' => 'employee.create', 'name' => 'Create Employee', 'description' => 'Create employee records'],
        ['code' => 'employee.update', 'name' => 'Update Employee', 'description' => 'Update employee records'],
        ['code' => 'employee.archive', 'name' => 'Archive Employee', 'description' => 'Archive employee records'],
        ['code' => 'employee.export', 'name' => 'Export Employee', 'description' => 'Export employee records'],
        ['code' => 'employee.view_salary', 'name' => 'View Employee Salary', 'description' => 'View employee salary fields'],
        ['code' => 'employee.approve', 'name' => 'Approve Employee', 'description' => 'Approve employee data changes'],
        ['code' => 'recruitment.view', 'name' => 'View Recruitment', 'description' => 'View recruitment records'],
        ['code' => 'recruitment.create', 'name' => 'Create Recruitment', 'description' => 'Create recruitment records'],
        ['code' => 'recruitment.publish', 'name' => 'Publish Recruitment', 'description' => 'Publish recruitment vacancies'],
        ['code' => 'asset.view', 'name' => 'View Asset', 'description' => 'View asset records'],
        ['code' => 'asset.assign', 'name' => 'Assign Asset', 'description' => 'Assign assets to employees'],
        ['code' => 'settings.view', 'name' => 'View Settings', 'description' => 'View system settings'],
        ['code' => 'settings.update', 'name' => 'Update Settings', 'description' => 'Update system settings'],
        ['code' => 'audit.view', 'name' => 'View Audit Log', 'description' => 'View audit logs'],
        ['code' => 'archive.manage', 'name' => 'Manage Archive', 'description' => 'Manage archived records'],
    ];

    public function run(): void
    {
        $company = Company::query()->where('name', 'PT Saneng')->firstOrFail();

        foreach ($this->permissions as $permission) {
            [$module, $action] = explode('.', $permission['code'], 2);

            Permission::withoutCompanyScope()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $permission['code'],
                ],
                [
                    'module' => $module,
                    'action' => $action,
                    'name' => $permission['name'],
                    'description' => $permission['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
