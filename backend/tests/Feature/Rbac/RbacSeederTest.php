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

    expect(Permission::count())->toBe(22);
    expect(Role::count())->toBe(5);

    $allPermissionCount = Permission::count();

    expect(Role::where('code', 'system_admin')->firstOrFail()->permissions()->count())->toBe($allPermissionCount);
    expect(Role::where('code', 'hr_manager')->firstOrFail()->permissions()->pluck('code')->sort()->values()->all())->toBe([
        'chatter.create',
        'chatter.view',
        'contract.create',
        'contract.terminate',
        'contract.view',
        'employee.approve',
        'employee.archive',
        'employee.create',
        'employee.export',
        'employee.update',
        'employee.view',
        'employee.view_sensitive',
        'offboarding.manage',
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
