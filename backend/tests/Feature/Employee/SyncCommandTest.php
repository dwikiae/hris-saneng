<?php

use App\Modules\Karyawan\Models\City;
use App\Modules\Karyawan\Models\Country;
use App\Modules\Karyawan\Models\Province;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('syncs wilayah reference data idempotently', function () {
    $this->artisan('wilayah:sync')->assertExitCode(0);
    $this->artisan('wilayah:sync')->assertExitCode(0);

    expect(Province::query()->count())->toBe(38)
        ->and(City::query()->count())->toBe(514)
        ->and(Province::query()->whereKey('95')->exists())->toBeTrue();
});

it('syncs country reference data idempotently', function () {
    $this->artisan('countries:sync')->assertExitCode(0);
    $this->artisan('countries:sync')->assertExitCode(0);

    expect(Country::query()->count())->toBe(249)
        ->and(Country::query()->whereKey('ID')->exists())->toBeTrue()
        ->and(Country::query()->whereKey('US')->exists())->toBeTrue()
        ->and(Country::query()->whereKey('SG')->exists())->toBeTrue();
});
