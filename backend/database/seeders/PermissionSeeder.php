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
        ['code' => 'employee.view_sensitive', 'name' => 'View Employee Sensitive Data', 'description' => 'View employee sensitive identity and bank fields'],
        ['code' => 'employee.view_salary', 'name' => 'View Employee Salary', 'description' => 'View employee salary fields'],
        ['code' => 'employee.approve', 'name' => 'Approve Employee', 'description' => 'Approve employee data changes'],
        ['code' => 'employee.override_number', 'name' => 'Override Employee Number', 'description' => 'Override generated employee numbers'],
        ['code' => 'karyawan.settings', 'name' => 'Karyawan Settings', 'description' => 'Manage employee module settings, work locations, and employee levels'],
        ['code' => 'recruitment.view', 'name' => 'View Recruitment', 'description' => 'View recruitment records'],
        ['code' => 'recruitment.create', 'name' => 'Create Recruitment', 'description' => 'Create recruitment records'],
        ['code' => 'recruitment.publish', 'name' => 'Publish Recruitment', 'description' => 'Publish recruitment vacancies'],
        ['code' => 'asset.view', 'name' => 'View Asset', 'description' => 'View asset records'],
        ['code' => 'asset.assign', 'name' => 'Assign Asset', 'description' => 'Assign assets to employees'],
        ['code' => 'settings.view', 'name' => 'View Settings', 'description' => 'View system settings'],
        ['code' => 'settings.update', 'name' => 'Update Settings', 'description' => 'Update system settings'],
        ['code' => 'audit.view', 'name' => 'View Audit Log', 'description' => 'View audit logs'],
        ['code' => 'audit.view_sensitive', 'name' => 'View Sensitive Audit Log', 'description' => 'View sensitive audit log fields'],
        ['code' => 'archive.manage', 'name' => 'Manage Archive', 'description' => 'Manage archived records'],
        ['code' => 'company.view', 'name' => 'View Company', 'description' => 'View company records'],
        ['code' => 'company.create', 'name' => 'Create Company', 'description' => 'Create company records'],
        ['code' => 'company.update', 'name' => 'Update Company', 'description' => 'Update company records'],
        ['code' => 'company.archive', 'name' => 'Archive Company', 'description' => 'Archive company records'],
        ['code' => 'user.view', 'name' => 'View User', 'description' => 'View user records'],
        ['code' => 'user.create', 'name' => 'Create User', 'description' => 'Create company users'],
        ['code' => 'user.update', 'name' => 'Update User', 'description' => 'Update company users'],
        ['code' => 'user.archive', 'name' => 'Archive User', 'description' => 'Archive company users'],
        ['code' => 'user.assign_role', 'name' => 'Assign User Role', 'description' => 'Assign roles to company users'],
        ['code' => 'notification.view', 'name' => 'View Notification', 'description' => 'View in-app notifications'],
        ['code' => 'notification.update', 'name' => 'Update Notification', 'description' => 'Mark notifications as read'],
    ];

    public function run(): void
    {
        $company = Company::query()->first();

        if (! $company) {
            return;
        }

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
