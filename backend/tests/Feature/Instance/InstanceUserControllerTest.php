<?php

use App\Jobs\Auth\SendUserInvitationJob;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('requires authentication for instance user endpoints', function () {
    $this->getJson('/api/v1/instance/users')->assertUnauthorized();
});

it('blocks company users from instance user endpoints', function () {
    $company = instanceUserCompany('PT Scoped');
    $user = instanceUser($company);

    $this->actingAs($user)
        ->getJson('/api/v1/instance/users')
        ->assertForbidden();
});

it('allows platform admin to create list show update archive and resend invitations', function () {
    Queue::fake();

    $admin = instanceUserAdmin();
    $company = instanceUserCompany('PT Access');
    $role = instanceUserRole($company, 'HR Staff');

    $response = $this->actingAs($admin)
        ->postJson('/api/v1/instance/users', [
            'name' => 'User Baru',
            'email' => 'userbaru@example.test',
            'companyIds' => [$company->id],
            'roleIds' => [$role->id],
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'instance.user.created')
        ->assertJsonPath('data.name', 'User Baru')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.invitationStatus', 'pending');

    $userId = $response->json('data.id');
    Queue::assertPushed(SendUserInvitationJob::class);
    expect(UserInvitation::query()->where('user_id', $userId)->count())->toBe(1);

    $this->actingAs($admin)
        ->getJson('/api/v1/instance/users')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1)
        ->assertJsonPath('data.items.0.roles.0.id', $role->id);

    $this->actingAs($admin)
        ->getJson("/api/v1/instance/users/{$userId}")
        ->assertOk()
        ->assertJsonPath('data.companies.0.id', $company->id);

    $this->actingAs($admin)
        ->putJson("/api/v1/instance/users/{$userId}", [
            'name' => 'User Baru Edit',
            'email' => 'userbaru@example.test',
            'companyIds' => [$company->id],
            'roleIds' => [],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'User Baru Edit')
        ->assertJsonPath('data.roles', []);

    $this->actingAs($admin)
        ->postJson("/api/v1/instance/users/{$userId}/resend-invitation")
        ->assertOk()
        ->assertJsonPath('message', 'instance.user.invitation_resent');

    expect(UserInvitation::query()->where('user_id', $userId)->count())->toBe(2);

    $this->actingAs($admin)
        ->deleteJson("/api/v1/instance/users/{$userId}")
        ->assertOk()
        ->assertJsonPath('message', 'instance.user.archived');

    expect(User::withArchived()->find($userId))->not->toBeNull()
        ->and(User::withArchived()->find($userId)?->archived_by)->toBe($admin->id);

    $this->assertDatabaseHas('activity_log', ['description' => 'instance.user.created']);
    $this->assertDatabaseHas('activity_log', ['description' => 'instance.user.updated']);
    $this->assertDatabaseHas('activity_log', ['description' => 'instance.user.archived']);
    $this->assertDatabaseHas('activity_log', ['description' => 'instance.user.invitation_resent']);
});

function instanceUserAdmin(): User
{
    return User::withoutCompanyScope()->create([
        'company_id' => null,
        'name' => 'Platform Administrator',
        'email' => uniqid('platform.user.', true).'@example.test',
        'password' => 'password',
    ]);
}

function instanceUserCompany(string $name): Company
{
    return Company::create([
        'name' => $name,
        'legal_name' => $name.' Legal',
        'slug' => str($name)->slug()->toString(),
    ]);
}

function instanceUser(Company $company): User
{
    return User::create([
        'company_id' => $company->id,
        'name' => 'Company User',
        'email' => uniqid('company.user.', true).'@example.test',
        'password' => 'password',
    ]);
}

function instanceUserRole(Company $company, string $name): Role
{
    return Role::create([
        'company_id' => $company->id,
        'code' => uniqid('role_', false),
        'name' => $name,
    ]);
}
