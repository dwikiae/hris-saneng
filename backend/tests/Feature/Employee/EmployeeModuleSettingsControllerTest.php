<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Modules\Karyawan\Models\EmployeeModuleSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns default employee module settings when no override exists', function () {
    [$company, $user] = employeeSettingsUser();

    $this->actingAs($user)
        ->getJson("/api/v1/{$company->id}/employees/settings")
        ->assertOk()
        ->assertJsonPath('data.employee_number_format', 'EMP-{SEQ:3}')
        ->assertJsonPath('data.number_format', 'EMP-{SEQ:3}')
        ->assertJsonPath('data.number_format_tokens_available.0.token', '{SEQ:N}')
        ->assertJsonPath('data.probation_days', 90)
        ->assertJsonPath('data.contract_expiry_notify_days', 30)
        ->assertJsonPath('data.pkwt_max_months', 60);
});

it('updates employee module settings per company', function () {
    [$firstCompany, $firstUser] = employeeSettingsUser();
    [$secondCompany, $secondUser] = employeeSettingsUser();

    $this->actingAs($firstUser)
        ->putJson("/api/v1/{$firstCompany->id}/employees/settings", [
            'employee_number_format' => 'SAN-{YYYY}-{SEQ5}',
            'probation_days' => 120,
            'contract_expiry_notify_days' => 45,
            'pkwt_max_months' => 36,
        ])
        ->assertOk()
        ->assertJsonPath('message', 'karyawan.settings.updated')
        ->assertJsonPath('data.employee_number_format', 'SAN-{YYYY}-{SEQ5}')
        ->assertJsonPath('data.pkwt_max_months', 36);

    $this->actingAs($secondUser)
        ->getJson("/api/v1/{$secondCompany->id}/employees/settings")
        ->assertOk()
        ->assertJsonPath('data.employee_number_format', 'EMP-{SEQ:3}');

    expect(EmployeeModuleSetting::forCompany((int) $firstCompany->id)->count())->toBe(4)
        ->and(EmployeeModuleSetting::forCompany((int) $secondCompany->id)->count())->toBe(0);
});

it('validates employee module settings payload', function () {
    [$company, $user] = employeeSettingsUser();

    $this->actingAs($user)
        ->putJson("/api/v1/{$company->id}/employees/settings", [
            'probation_days' => -1,
            'contract_expiry_notify_days' => 500,
            'pkwt_max_months' => 61,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['probation_days', 'contract_expiry_notify_days', 'pkwt_max_months']);
});

it('previews employee number format with token metadata', function () {
    [$company, $user] = employeeSettingsUser();

    $this->travelTo(now()->setDate(2026, 6, 9));

    $this->actingAs($user)
        ->postJson("/api/v1/{$company->id}/employees/settings/number-format/preview", [
            'format' => '{DEPT_CODE}-{YYYY}-{SEQ:4}',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'karyawan.settings.number_format_preview')
        ->assertJsonPath('data.preview', 'OPS-2026-0001')
        ->assertJsonPath('data.next_sequence', 1)
        ->assertJsonPath('data.tokens_used', ['DEPT_CODE', 'YYYY', 'SEQ'])
        ->assertJsonPath('data.tokens_available.0.token', '{SEQ:N}');
});

it('rejects invalid employee number format tokens', function () {
    [$company, $user] = employeeSettingsUser();

    $this->actingAs($user)
        ->postJson("/api/v1/{$company->id}/employees/settings/number-format/preview", [
            'format' => 'EMP-{UNKNOWN}-{SEQ:3}',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Employee number token UNKNOWN is not recognized.');

    $this->actingAs($user)
        ->putJson("/api/v1/{$company->id}/employees/settings", [
            'employee_number_format' => 'EMP-{YYYY}',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Employee number format must contain a sequence token.');
});

/**
 * @return array{0: Company, 1: User}
 */
function employeeSettingsUser(): array
{
    $company = Company::create([
        'name' => uniqid('PT Settings ', false),
        'legal_name' => uniqid('PT Settings Legal ', false),
    ]);
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Employee Settings User',
        'email' => uniqid('employee.settings.', true).'@example.test',
        'password' => 'password',
    ]);
    $role = Role::create([
        'company_id' => $company->id,
        'code' => uniqid('employee_settings_', false),
        'name' => 'Employee Settings',
    ]);
    $permission = Permission::create([
        'company_id' => $company->id,
        'code' => 'karyawan.settings',
        'module' => 'karyawan',
        'action' => 'settings',
        'name' => 'Karyawan Settings',
    ]);
    $role->permissions()->attach($permission->id);
    $user->roles()->attach($role->id);

    return [$company, $user];
}
