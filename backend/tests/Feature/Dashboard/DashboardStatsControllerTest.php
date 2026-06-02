<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns dashboard stats for the authenticated users company', function () {
    $company = dashboardCompany('PT Dashboard');
    $user = dashboardUser($company, 'dashboard_manager', ['employee.approve']);

    $activeEmployee = dashboardEmployee($company, $user, [
        'employee_number' => 'DASH-ACTIVE-001',
        'name' => 'Active Employee',
        'status' => Employee::ACTIVE,
    ]);
    $pendingEmployee = dashboardEmployee($company, $user, [
        'employee_number' => 'DASH-PENDING-001',
        'name' => 'Pending Employee',
        'status' => Employee::PENDING,
        'created_by' => $user->id,
    ]);

    activity()->causedBy($user)->performedOn($activeEmployee)->log('dashboard.activity.company_a');

    $this->actingAs($user)
        ->getJson('/api/v1/dashboard/stats')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'dashboard.stats')
        ->assertJsonPath('data.stats.total_employees', 2)
        ->assertJsonPath('data.stats.present_today', 0)
        ->assertJsonPath('data.stats.absent_today', 0)
        ->assertJsonPath('data.stats.leave_today', 0)
        ->assertJsonPath('data.pending_approvals.0.resource_id', $pendingEmployee->id)
        ->assertJsonPath('data.pending_approvals.0.type', 'employee')
        ->assertJsonPath('data.recent_activities.0.title', 'dashboard.activity.company_a');
});

it('rejects unauthenticated dashboard stats requests', function () {
    $this->getJson('/api/v1/dashboard/stats')
        ->assertUnauthorized()
        ->assertJsonPath('success', false);
});

it('does not expose dashboard data from another company', function () {
    $companyA = dashboardCompany('PT Dashboard A');
    $companyB = dashboardCompany('PT Dashboard B');
    $userA = dashboardUser($companyA, 'dashboard_company_a', ['employee.approve']);
    $userB = dashboardUser($companyB, 'dashboard_company_b', ['employee.approve']);

    $employeeA = dashboardEmployee($companyA, $userA, [
        'employee_number' => 'DASH-A-001',
        'name' => 'Company A Employee',
        'status' => Employee::PENDING,
        'created_by' => $userA->id,
    ]);
    $employeeB = dashboardEmployee($companyB, $userB, [
        'employee_number' => 'DASH-B-001',
        'name' => 'Company B Employee',
        'status' => Employee::PENDING,
        'created_by' => $userB->id,
    ]);

    activity()->causedBy($userA)->performedOn($employeeA)->log('dashboard.activity.company_a');
    activity()->causedBy($userB)->performedOn($employeeB)->log('dashboard.activity.company_b');

    $response = $this->actingAs($userA)
        ->getJson('/api/v1/dashboard/stats')
        ->assertOk()
        ->assertJsonPath('data.stats.total_employees', 1)
        ->assertJsonPath('data.pending_approvals.0.resource_id', $employeeA->id);

    expect(collect($response->json('data.pending_approvals'))->pluck('resource_id')->all())
        ->not->toContain($employeeB->id);

    expect(collect($response->json('data.recent_activities'))->pluck('title')->all())
        ->not->toContain('dashboard.activity.company_b');
});

it('hides pending approvals for users without approval permission', function () {
    $company = dashboardCompany('PT Dashboard Viewer');
    $user = dashboardUser($company, 'dashboard_viewer', ['employee.view']);

    dashboardEmployee($company, $user, [
        'employee_number' => 'DASH-PENDING-VIEWER-001',
        'name' => 'Pending Viewer Employee',
        'status' => Employee::PENDING,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/dashboard/stats')
        ->assertOk()
        ->assertJsonPath('data.stats.total_employees', 1)
        ->assertJsonPath('data.pending_approvals', []);
});

function dashboardCompany(string $name): Company
{
    return Company::create(['name' => $name, 'legal_name' => $name]);
}

/**
 * @param  array<int, string>  $permissionCodes
 */
function dashboardUser(Company $company, string $code, array $permissionCodes): User
{
    $user = User::create([
        'company_id' => $company->id,
        'name' => $code,
        'email' => $code.'@example.test',
        'password' => 'password',
    ]);
    $role = Role::create([
        'company_id' => $company->id,
        'code' => $code,
        'name' => $code,
    ]);

    foreach ($permissionCodes as $permissionCode) {
        $permission = dashboardPermission($company, $permissionCode);
        $role->permissions()->attach($permission->id);
    }

    $user->roles()->attach($role->id);

    return $user;
}

function dashboardPermission(Company $company, string $code): Permission
{
    [$module, $action] = explode('.', $code, 2);

    return Permission::create([
        'company_id' => $company->id,
        'code' => $code,
        'module' => $module,
        'action' => $action,
        'name' => $code,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function dashboardEmployee(Company $company, User $actor, array $overrides = []): Employee
{
    return Employee::create(array_merge([
        'company_id' => $company->id,
        'employee_number' => 'DASH-EMP-'.uniqid(),
        'name' => 'Dashboard Employee',
        'email' => uniqid('dashboard.employee').'@example.test',
        'nik' => '3374010101010001',
        'npwp' => '09.123.456.7-891.000',
        'bank_account_number' => '1234567890',
        'salary' => '10000000',
        'allowances' => '1500000',
        'deductions' => '250000',
        'consent_at' => now(),
        'consent_by' => $actor->id,
        'status' => Employee::ACTIVE,
        'created_by' => $actor->id,
        'updated_by' => $actor->id,
    ], $overrides));
}
