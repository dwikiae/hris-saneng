<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds permissions and default role assignments idempotently', function () {
    Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);

    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);

    expect(Permission::count())->toBe(31);
    expect(Role::count())->toBe(7);

    $allPermissionCount = Permission::count();

    expect(Role::where('code', 'system_admin')->firstOrFail()->permissions()->count())->toBe($allPermissionCount);
    expect(Role::where('code', 'system_admin')->firstOrFail()->name)->toBe('Platform Administrator');
    expect(Role::where('code', 'hr_manager')->firstOrFail()->permissions()->pluck('code')->sort()->values()->all())->toBe([
        'employee.approve',
        'employee.archive',
        'employee.create',
        'employee.export',
        'employee.override_number',
        'employee.update',
        'employee.view',
        'employee.view_salary',
        'employee.view_sensitive',
        'recruitment.create',
        'recruitment.publish',
        'recruitment.view',
    ]);
    expect(Role::where('code', 'hr_staff')->firstOrFail()->permissions()->pluck('code')->sort()->values()->all())->toBe([
        'employee.create',
        'employee.view',
        'recruitment.view',
    ]);
    expect(Role::where('code', 'approver')->firstOrFail()->permissions()->pluck('code')->sort()->values()->all())->toBe([
        'employee.approve',
        'employee.view',
    ]);
    expect(Role::where('code', 'employee')->firstOrFail()->permissions()->count())->toBe(0);
});
