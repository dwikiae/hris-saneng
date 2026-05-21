<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $this->user = User::create([
        'company_id'           => $company->id,
        'name'                 => 'Test User',
        'email'                => 'admin@saneng.co.id',
        'password'             => 'Admin@1234',
        'language_preference'  => 'id',
        'force_password_reset' => false,
        'login_attempts'       => 0,
    ]);
});

it('changes password successfully and clears force_password_reset', function () {
    $token = $this->user->createToken('test')->plainTextToken;

    $this->postJson('/api/v1/auth/change-password', [
        'current_password'          => 'Admin@1234',
        'new_password'              => 'NewPass@5678',
        'new_password_confirmation' => 'NewPass@5678',
    ], ['Authorization' => "Bearer $token"])
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->user->refresh();
    expect($this->user->force_password_reset)->toBeFalse();
});

it('returns 422 when current_password is wrong', function () {
    $token = $this->user->createToken('test')->plainTextToken;

    $this->postJson('/api/v1/auth/change-password', [
        'current_password'          => 'WrongPassword',
        'new_password'              => 'NewPass@5678',
        'new_password_confirmation' => 'NewPass@5678',
    ], ['Authorization' => "Bearer $token"])
        ->assertStatus(422);
});

it('returns 422 when new_password is too short', function () {
    $token = $this->user->createToken('test')->plainTextToken;

    $this->postJson('/api/v1/auth/change-password', [
        'current_password'          => 'Admin@1234',
        'new_password'              => 'short',
        'new_password_confirmation' => 'short',
    ], ['Authorization' => "Bearer $token"])
        ->assertStatus(422);
});

it('returns 401 when unauthenticated', function () {
    $this->postJson('/api/v1/auth/change-password', [
        'current_password'          => 'Admin@1234',
        'new_password'              => 'NewPass@5678',
        'new_password_confirmation' => 'NewPass@5678',
    ])->assertStatus(401);
});

it('allows access when force_password_reset is true', function () {
    $this->user->update(['force_password_reset' => true]);
    $token = $this->user->createToken('test')->plainTextToken;

    $this->postJson('/api/v1/auth/change-password', [
        'current_password'          => 'Admin@1234',
        'new_password'              => 'NewPass@5678',
        'new_password_confirmation' => 'NewPass@5678',
    ], ['Authorization' => "Bearer $token"])
        ->assertStatus(200);
});
