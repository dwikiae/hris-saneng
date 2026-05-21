<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createUserTestCompany(): Company
{
    return Company::create([
        'name' => 'PT Saneng',
        'legal_name' => 'PT Saneng',
    ]);
}

it('excludes archived users from default query', function () {
    $company = createUserTestCompany();
    $active = User::create(['company_id' => $company->id, 'name' => 'Active User', 'email' => 'active@example.test', 'password' => 'secret']);
    $archived = User::create(['company_id' => $company->id, 'name' => 'Archived User', 'email' => 'archived@example.test', 'password' => 'secret']);

    $archived->archive($active->id);

    expect(User::pluck('email')->all())->toBe(['active@example.test']);
});

it('can archive a user', function () {
    $company = createUserTestCompany();
    $actor = User::create(['company_id' => $company->id, 'name' => 'Actor User', 'email' => 'actor@example.test', 'password' => 'secret']);
    $target = User::create(['company_id' => $company->id, 'name' => 'Target User', 'email' => 'target@example.test', 'password' => 'secret']);

    $target->archive($actor->id);
    $target = User::withArchived()->whereKey($target->id)->firstOrFail();

    expect($target->archived_at)->not->toBeNull()
        ->and($target->archived_by)->toBe($actor->id);
});

it('returns archived users when using withArchived scope', function () {
    $company = createUserTestCompany();
    $active = User::create(['company_id' => $company->id, 'name' => 'Active User', 'email' => 'active-scope@example.test', 'password' => 'secret']);
    $archived = User::create(['company_id' => $company->id, 'name' => 'Archived User', 'email' => 'archived-scope@example.test', 'password' => 'secret']);

    $archived->archive($active->id);

    expect(User::count())->toBe(1)
        ->and(User::withArchived()->count())->toBe(2);
});

it('correctly identifies a locked user', function () {
    $user = new User(['locked_until' => now()->addMinutes(5)]);

    expect($user->isLocked())->toBeTrue();
});

it('correctly identifies an unlocked user', function () {
    $user = new User(['locked_until' => now()->subMinute()]);

    expect($user->isLocked())->toBeFalse();
});
