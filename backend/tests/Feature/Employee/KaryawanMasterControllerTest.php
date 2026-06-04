<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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
