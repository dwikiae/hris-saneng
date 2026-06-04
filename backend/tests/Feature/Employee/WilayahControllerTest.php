<?php

use App\Models\Company;
use App\Models\User;
use App\Modules\Karyawan\Database\Seeders\CitySeeder;
use App\Modules\Karyawan\Database\Seeders\CountrySeeder;
use App\Modules\Karyawan\Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication for wilayah endpoints', function () {
    $this->getJson('/api/v1/instance/wilayah/provinces')
        ->assertUnauthorized();
});

it('lists provinces and cities without special permission', function () {
    $this->seed(ProvinceSeeder::class);
    $this->seed(CitySeeder::class);
    $user = wilayahUser();

    $this->actingAs($user)
        ->getJson('/api/v1/instance/wilayah/provinces')
        ->assertOk()
        ->assertJsonCount(38, 'data')
        ->assertJsonFragment(['code' => '95', 'name' => 'Papua Pegunungan'])
        ->assertJsonFragment(['code' => '92', 'name' => 'Papua Barat Daya']);

    $this->actingAs($user)
        ->getJson('/api/v1/instance/wilayah/provinces/31/cities')
        ->assertOk()
        ->assertJsonPath('data.0.provinceCode', '31');
});

it('lists active ISO countries without special permission', function () {
    $this->seed(CountrySeeder::class);
    $user = wilayahUser();

    $this->actingAs($user)
        ->getJson('/api/v1/instance/wilayah/countries')
        ->assertOk()
        ->assertJsonFragment(['code' => 'ID', 'name' => 'Indonesia', 'isActive' => true])
        ->assertJsonFragment(['code' => 'US', 'name' => 'United States', 'isActive' => true])
        ->assertJsonFragment(['code' => 'SG', 'name' => 'Singapore', 'isActive' => true]);
});

function wilayahUser(): User
{
    $company = Company::create(['name' => 'PT Wilayah', 'legal_name' => 'PT Wilayah']);

    return User::create([
        'company_id' => $company->id,
        'name' => 'Wilayah User',
        'email' => uniqid('wilayah.', true).'@example.test',
        'password' => 'password',
    ]);
}
