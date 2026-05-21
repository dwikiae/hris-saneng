<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createArchiveTraitCompany(): Company
{
    return Company::create([
        'name' => 'PT Saneng',
        'legal_name' => 'PT Saneng',
    ]);
}

it('sets archived_at and archived_by when archiving', function () {
    $company = createArchiveTraitCompany();
    $actor = User::create(['company_id' => $company->id, 'name' => 'Actor User', 'email' => 'archive-actor@example.test', 'password' => 'secret']);
    $target = User::create(['company_id' => $company->id, 'name' => 'Target User', 'email' => 'archive-target@example.test', 'password' => 'secret']);

    $target->archive($actor->id);
    $target = User::withArchived()->whereKey($target->id)->firstOrFail();

    expect($target->archived_at)->not->toBeNull()
        ->and($target->archived_by)->toBe($actor->id);
});

it('global scope filters out archived records', function () {
    $company = createArchiveTraitCompany();
    $active = User::create(['company_id' => $company->id, 'name' => 'Active User', 'email' => 'archive-active@example.test', 'password' => 'secret']);
    $archived = User::create(['company_id' => $company->id, 'name' => 'Archived User', 'email' => 'archive-hidden@example.test', 'password' => 'secret']);

    $archived->archive($active->id);

    expect(User::all())->toHaveCount(1)
        ->and(User::first()->email)->toBe('archive-active@example.test');
});

it('withArchived scope includes archived records', function () {
    $company = createArchiveTraitCompany();
    $active = User::create(['company_id' => $company->id, 'name' => 'Active User', 'email' => 'archive-visible-active@example.test', 'password' => 'secret']);
    $archived = User::create(['company_id' => $company->id, 'name' => 'Archived User', 'email' => 'archive-visible-hidden@example.test', 'password' => 'secret']);

    $archived->archive($active->id);

    expect(User::withArchived()->count())->toBe(2)
        ->and(User::withArchived()->archived()->count())->toBe(1);
});
