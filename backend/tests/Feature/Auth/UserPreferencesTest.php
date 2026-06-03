<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('updates the authenticated users language preference', function () {
    $company = Company::create(['name' => 'PT Preference', 'legal_name' => 'PT Preference']);
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Preference User',
        'email' => 'preference@example.test',
        'password' => 'password',
        'language_preference' => 'id',
    ]);
    $token = $user->createToken('test')->plainTextToken;

    $this->patchJson('/api/v1/users/me/preferences', [
        'language_preference' => 'en',
    ], ['Authorization' => "Bearer $token"])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'user.preferences.updated')
        ->assertJsonPath('data.language_preference', 'en');

    expect($user->refresh()->language_preference)->toBe('en');
});

it('rejects unsupported language preferences', function () {
    $company = Company::create(['name' => 'PT Preference', 'legal_name' => 'PT Preference']);
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Preference User',
        'email' => 'preference-invalid@example.test',
        'password' => 'password',
        'language_preference' => 'id',
    ]);
    $token = $user->createToken('test')->plainTextToken;

    $this->patchJson('/api/v1/users/me/preferences', [
        'language_preference' => 'fr',
    ], ['Authorization' => "Bearer $token"])
        ->assertUnprocessable();
});

it('rejects unauthenticated preference updates', function () {
    $this->patchJson('/api/v1/users/me/preferences', [
        'language_preference' => 'en',
    ])->assertUnauthorized();
});
