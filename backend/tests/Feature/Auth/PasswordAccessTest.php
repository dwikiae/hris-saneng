<?php

use App\Jobs\Auth\SendResetPasswordJob;
use App\Models\Company;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('queues reset password email for known email and stays safe for unknown email', function () {
    Queue::fake();
    $company = passwordAccessCompany();
    $user = passwordAccessUser($company, 'reset@example.test');

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
        ->assertOk()
        ->assertJsonPath('message', 'auth.password_reset.requested');

    Queue::assertPushed(SendResetPasswordJob::class);
    $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'unknown@example.test'])
        ->assertOk()
        ->assertJsonPath('message', 'auth.password_reset.requested');
});

it('resets password with a valid unexpired token and rejects invalid token', function () {
    $company = passwordAccessCompany();
    $user = passwordAccessUser($company, 'valid-reset@example.test');
    $token = 'valid-reset-token';

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => hash('sha256', $token),
        'created_at' => now(),
    ]);

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'token' => 'wrong-token',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertUnprocessable();

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertOk()
        ->assertJsonPath('message', 'auth.password_reset.completed');

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

it('sets password from invitation token and rejects expired token', function () {
    $company = passwordAccessCompany();
    $user = passwordAccessUser($company, 'invite@example.test');
    $token = 'valid-invitation-token';

    UserInvitation::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDay(),
    ]);
    UserInvitation::create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', 'expired-token'),
        'expires_at' => now()->subMinute(),
    ]);

    $this->postJson('/api/v1/auth/set-password', [
        'token' => 'expired-token',
        'password' => 'invited-password',
        'password_confirmation' => 'invited-password',
    ])->assertUnprocessable();

    $this->postJson('/api/v1/auth/set-password', [
        'token' => $token,
        'password' => 'invited-password',
        'password_confirmation' => 'invited-password',
    ])->assertOk()
        ->assertJsonPath('message', 'auth.invitation.accepted');

    expect(Hash::check('invited-password', $user->refresh()->password))->toBeTrue()
        ->and(UserInvitation::query()->where('token_hash', hash('sha256', $token))->first()?->accepted_at)->not->toBeNull();
});

function passwordAccessCompany(): Company
{
    return Company::create([
        'name' => uniqid('PT Password ', false),
        'legal_name' => 'PT Password Legal',
        'slug' => uniqid('pt-password-', false),
    ]);
}

function passwordAccessUser(Company $company, string $email): User
{
    return User::create([
        'company_id' => $company->id,
        'name' => 'Password User',
        'email' => $email,
        'password' => 'old-password',
        'force_password_reset' => true,
    ]);
}
