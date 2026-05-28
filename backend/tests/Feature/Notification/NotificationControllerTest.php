<?php

use App\Application\Notification\NotificationService;
use App\Jobs\Notification\SendNotificationEmailJob;
use App\Models\Company;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('creates in-app notification with redacted sensitive data', function () {
    $company = Company::query()->create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $user = milestone11NotificationUser($company, []);

    $notification = app(NotificationService::class)->createInAppNotification(
        $user,
        'system',
        'notification.mail_subject',
        'notification.mail_body',
        [
            'employee_id' => 10,
            'salary' => '1000000',
            'api_token' => 'secret-token',
        ]
    );

    expect($notification->company_id)->toBe($company->id)
        ->and($notification->user_id)->toBe($user->id)
        ->and($notification->data['employee_id'])->toBe(10)
        ->and($notification->data['salary'])->toBe('[REDACTED]')
        ->and($notification->data['api_token'])->toBe('[REDACTED]');
});

it('lists only current user notifications', function () {
    $company = Company::query()->create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $actor = milestone11NotificationUser($company, ['notification.view']);
    $other = milestone11NotificationUser($company, ['notification.view']);

    Notification::query()->create(milestone11NotificationPayload($actor));
    Notification::query()->create(milestone11NotificationPayload($other));

    $this->actingAs($actor)
        ->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'notification.list')
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.user_id', null);
});

it('marks only owned notification as read', function () {
    $company = Company::query()->create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $actor = milestone11NotificationUser($company, ['notification.update']);
    $other = milestone11NotificationUser($company, ['notification.update']);
    $owned = Notification::query()->create(milestone11NotificationPayload($actor));
    $foreign = Notification::query()->create(milestone11NotificationPayload($other));

    $this->actingAs($actor)
        ->postJson("/api/v1/notifications/{$owned->id}/read")
        ->assertOk()
        ->assertJsonPath('message', 'notification.marked_read');

    $this->actingAs($actor)
        ->postJson("/api/v1/notifications/{$foreign->id}/read")
        ->assertNotFound()
        ->assertJsonPath('message', 'notification.not_found');

    expect($owned->refresh()->read_at)->not->toBeNull()
        ->and($foreign->refresh()->read_at)->toBeNull();
});

it('queues email notification without sending in the request cycle', function () {
    Queue::fake();

    $company = Company::query()->create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $user = milestone11NotificationUser($company, []);

    app(NotificationService::class)->notifyUser(
        $user,
        'system',
        'notification.mail_subject',
        'notification.mail_body',
        [],
        queueEmail: true
    );

    Queue::assertPushed(SendNotificationEmailJob::class);
});

/**
 * @return array<string, mixed>
 */
function milestone11NotificationPayload(User $user): array
{
    return [
        'company_id' => $user->company_id,
        'user_id' => $user->id,
        'type' => 'system',
        'title_key' => 'notification.mail_subject',
        'body_key' => 'notification.mail_body',
        'data' => [],
    ];
}

function milestone11NotificationUser(Company $company, array $permissions): User
{
    $user = User::query()->create([
        'company_id' => $company->id,
        'name' => 'Notification User',
        'email' => uniqid('notification.user.', true).'@example.test',
        'password' => 'password',
    ]);

    if ($permissions === []) {
        return $user;
    }

    $role = Role::query()->create([
        'company_id' => $company->id,
        'code' => uniqid('notification_role_', false),
        'name' => 'Notification Role',
    ]);

    foreach ($permissions as $code) {
        [$module, $action] = explode('.', $code, 2);
        $permission = Permission::query()->create([
            'company_id' => $company->id,
            'code' => $code,
            'module' => $module,
            'action' => $action,
            'name' => $code,
        ]);

        $role->permissions()->attach($permission->id);
    }

    $user->roles()->attach($role->id);

    return $user;
}
