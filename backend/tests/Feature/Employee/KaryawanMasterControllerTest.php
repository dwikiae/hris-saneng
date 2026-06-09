<?php

use App\Models\Company;
use App\Models\Bank;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\EducationLevel;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Religion;
use App\Models\Role;
use App\Models\User;
use App\Modules\Karyawan\Models\ContractType;
use App\Modules\Karyawan\Models\City;
use App\Modules\Karyawan\Models\EmployeeLevel;
use App\Modules\Karyawan\Models\Province;
use App\Modules\Karyawan\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication for Karyawan master endpoints', function () {
    $company = Company::create(['name' => 'PT Secure', 'legal_name' => 'PT Secure']);

    $this->getJson("/api/v1/{$company->id}/employees/master/work-locations")
        ->assertUnauthorized();
});

it('requires karyawan settings permission for Karyawan master endpoints', function () {
    [$company, $user] = karyawanMasterUser(false);

    $this->actingAs($user)
        ->getJson("/api/v1/{$company->id}/employees/master/work-locations")
        ->assertForbidden();
});

it('creates lists updates archives and restores work locations within one company', function () {
    [$company, $user] = karyawanMasterUser();
    karyawanMasterCity();

    $response = $this->actingAs($user)
        ->postJson("/api/v1/{$company->id}/employees/master/work-locations", [
            'code' => 'HO',
            'name' => 'Head Office',
            'address' => 'Jl. HR 1',
            'city_id' => '31.71',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'karyawan.work_locations.created')
        ->assertJsonPath('data.code', 'HO')
        ->assertJsonPath('data.cityId', '31.71');

    $id = $response->json('data.id');

    $this->actingAs($user)
        ->getJson("/api/v1/{$company->id}/employees/master/work-locations")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($user)
        ->postJson("/api/v1/{$company->id}/employees/master/work-locations/{$id}", [
            'code' => 'HO',
            'name' => 'Head Office Updated',
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Head Office Updated')
        ->assertJsonPath('data.isActive', false);

    $this->actingAs($user)
        ->postJson("/api/v1/{$company->id}/employees/master/work-locations/{$id}/archive")
        ->assertOk()
        ->assertJsonPath('message', 'karyawan.work_locations.archived');

    expect(WorkLocation::query()->find($id))->toBeNull()
        ->and(WorkLocation::withArchived()->find($id))->not->toBeNull();

    $this->actingAs($user)
        ->postJson("/api/v1/{$company->id}/employees/master/work-locations/{$id}/restore")
        ->assertOk()
        ->assertJsonPath('message', 'karyawan.work_locations.restored');

    expect(WorkLocation::query()->find($id))->not->toBeNull();
});

it('isolates Karyawan master data by company and allows duplicate code across companies', function () {
    [$firstCompany, $firstUser] = karyawanMasterUser();
    [$secondCompany, $secondUser] = karyawanMasterUser();

    WorkLocation::create(['company_id' => $firstCompany->id, 'code' => 'HO', 'name' => 'First HO']);
    WorkLocation::create(['company_id' => $secondCompany->id, 'code' => 'HO', 'name' => 'Second HO']);

    $this->actingAs($firstUser)
        ->getJson("/api/v1/{$firstCompany->id}/employees/master/work-locations")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'First HO');

    $this->actingAs($firstUser)
        ->postJson("/api/v1/{$firstCompany->id}/employees/master/work-locations", [
            'code' => 'HO',
            'name' => 'Duplicate HO',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);

    $this->actingAs($secondUser)
        ->postJson("/api/v1/{$secondCompany->id}/employees/master/work-locations", [
            'code' => 'SITE',
            'name' => 'Second Site',
        ])
        ->assertCreated();
});

it('manages employee levels and blocks cross company access', function () {
    [$firstCompany, $firstUser] = karyawanMasterUser();
    [$secondCompany] = karyawanMasterUser();

    $response = $this->actingAs($firstUser)
        ->postJson("/api/v1/{$firstCompany->id}/employees/master/employee-levels", [
            'code' => 'L1',
            'name' => 'Staff',
            'description' => 'Entry level',
            'order' => 1,
        ])
        ->assertCreated()
        ->assertJsonPath('data.order', 1);

    $id = $response->json('data.id');

    $this->actingAs($firstUser)
        ->putJson("/api/v1/{$firstCompany->id}/employees/master/employee-levels/{$id}", [
            'code' => 'L1',
            'name' => 'Senior Staff',
            'order' => 2,
            'is_active' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Senior Staff')
        ->assertJsonPath('data.order', 2);

    $this->actingAs($firstUser)
        ->getJson("/api/v1/{$secondCompany->id}/employees/master/employee-levels/{$id}")
        ->assertBadRequest();

    expect(EmployeeLevel::forCompany((int) $firstCompany->id)->count())->toBe(1);
});

it('manages departments with active employee counts', function () {
    [$company, $user] = karyawanMasterUser();

    $response = $this->actingAs($user)
        ->postJson("/api/v1/{$company->id}/employees/master/departments", [
            'code' => 'OPS',
            'name' => 'Operasional',
            'description' => 'Operasional lapangan',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'karyawan.departments.created')
        ->assertJsonPath('data.code', 'OPS');

    $departmentId = $response->json('data.id');

    Employee::create([
        'company_id' => $company->id,
        'employee_number' => 'OPS-001',
        'name' => 'Ops Employee',
        'department_id' => $departmentId,
        'consent_at' => now(),
        'consent_by' => $user->id,
        'status' => Employee::ACTIVE,
    ]);

    $this->actingAs($user)
        ->getJson("/api/v1/{$company->id}/employees/master/departments")
        ->assertOk()
        ->assertJsonPath('data.0.totalEmployees', 1);

    $this->actingAs($user)
        ->patchJson("/api/v1/{$company->id}/employees/master/departments/{$departmentId}", [
            'code' => 'OPS',
            'name' => 'Operasional Updated',
            'parent_id' => null,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Operasional Updated');

    $this->actingAs($user)
        ->patchJson("/api/v1/{$company->id}/employees/master/departments/{$departmentId}/archive")
        ->assertOk()
        ->assertJsonPath('message', 'karyawan.departments.archived');

    expect(Department::query()->find($departmentId))->toBeNull()
        ->and(Department::withArchived()->find($departmentId))->not->toBeNull();

    $this->actingAs($user)
        ->patchJson("/api/v1/{$company->id}/employees/master/departments/{$departmentId}/restore")
        ->assertOk()
        ->assertJsonPath('message', 'karyawan.departments.restored');
});

it('manages job positions and filters them by department', function () {
    [$company, $user] = karyawanMasterUser();
    $operations = Department::create(['company_id' => $company->id, 'code' => 'OPS', 'name' => 'Operasional']);
    $finance = Department::create(['company_id' => $company->id, 'code' => 'FIN', 'name' => 'Finance']);

    $this->actingAs($user)
        ->postJson("/api/v1/{$company->id}/employees/master/job-positions", [
            'code' => 'OPS-STF',
            'name' => 'Staff Operasional',
            'department_id' => $operations->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.departmentId', $operations->id);

    $this->actingAs($user)
        ->postJson("/api/v1/{$company->id}/employees/master/job-positions", [
            'code' => 'FIN-STF',
            'name' => 'Staff Finance',
            'department_id' => $finance->id,
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->getJson("/api/v1/{$company->id}/employees/master/job-positions?department_id={$operations->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'OPS-STF');
});

it('manages default-backed employee master resources', function () {
    [$company, $user] = karyawanMasterUser();

    $resources = [
        'contract-types' => ['code' => 'PKWT', 'name' => 'PKWT', 'type' => 'pkwt', 'max_duration_months' => 24],
        'religions' => ['code' => 'ISLAM', 'name' => 'Islam'],
        'banks' => ['code' => 'BCA', 'name' => 'BCA', 'swift' => 'CENAIDJA'],
        'document-types' => ['code' => 'KTP', 'name' => 'KTP', 'is_mandatory' => true],
        'education-levels' => ['code' => 'S1', 'name' => 'S1', 'order' => 7],
    ];

    foreach ($resources as $resource => $payload) {
        $response = $this->actingAs($user)
            ->postJson("/api/v1/{$company->id}/employees/master/{$resource}", $payload)
            ->assertCreated();

        $id = $response->json('data.id');

        $this->actingAs($user)
            ->patchJson("/api/v1/{$company->id}/employees/master/{$resource}/{$id}/archive")
            ->assertOk();

        $this->actingAs($user)
            ->patchJson("/api/v1/{$company->id}/employees/master/{$resource}/{$id}/restore")
            ->assertOk();
    }
});

it('seeds default employee master data when a company is created from instance settings', function () {
    $admin = User::create([
        'company_id' => null,
        'name' => 'Instance Admin',
        'email' => 'instance.admin@example.test',
        'password' => 'password',
    ]);

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/instance/companies', [
            'name' => 'PT Defaults',
            'legal_name' => 'PT Defaults',
        ])
        ->assertCreated();

    $companyId = $response->json('data.id');

    expect(ContractType::withoutCompanyScope()->where('company_id', $companyId)->count())->toBe(2)
        ->and(Religion::withoutCompanyScope()->where('company_id', $companyId)->count())->toBe(6)
        ->and(Bank::withoutCompanyScope()->where('company_id', $companyId)->count())->toBe(10)
        ->and(DocumentType::withoutCompanyScope()->where('company_id', $companyId)->count())->toBe(6)
        ->and(EducationLevel::withoutCompanyScope()->where('company_id', $companyId)->count())->toBe(9)
        ->and(EmploymentType::withoutCompanyScope()->where('company_id', $companyId)->whereIn('code', ['PKWT', 'PKWTT'])->count())->toBe(2);
});

it('seeds default employee master data when setup wizard creates the first company', function () {
    $this->postJson('/api/v1/setup/complete', [
        'company' => [
            'name' => 'PT Setup Defaults',
            'legal_name' => 'PT Setup Defaults',
        ],
        'admin' => [
            'name' => 'Setup Admin',
            'email' => 'setup.admin@example.test',
            'password' => 'Password1',
        ],
    ])
        ->assertCreated();

    $company = Company::query()->firstOrFail();

    expect(ContractType::withoutCompanyScope()->where('company_id', $company->id)->count())->toBe(2)
        ->and(Religion::withoutCompanyScope()->where('company_id', $company->id)->count())->toBe(6);
});

function karyawanMasterCity(): void
{
    Province::create(['code' => '31', 'name' => 'DKI Jakarta']);
    City::create(['code' => '31.71', 'province_code' => '31', 'name' => 'Kota Jakarta Selatan']);
}

/**
 * @return array{0: Company, 1: User}
 */
function karyawanMasterUser(bool $withPermission = true): array
{
    $company = Company::create([
        'name' => uniqid('PT Karyawan ', false),
        'legal_name' => uniqid('PT Karyawan Legal ', false),
    ]);
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Karyawan Settings User',
        'email' => uniqid('karyawan.settings.', true).'@example.test',
        'password' => 'password',
    ]);

    if ($withPermission) {
        $role = Role::create([
            'company_id' => $company->id,
            'code' => uniqid('karyawan_settings_', false),
            'name' => 'Karyawan Settings',
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
    }

    return [$company, $user];
}
