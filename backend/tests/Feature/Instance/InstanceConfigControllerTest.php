<?php

use App\Jobs\Instance\SendPlatformConfigTestSmtpJob;
use App\Mail\Instance\PlatformConfigTestSmtpMail;
use App\Models\Company;
use App\Models\InstanceSetting;
use App\Models\User;
use App\Repositories\Contracts\InstanceSettingsRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('requires authentication for instance config endpoints', function () {
    $this->getJson('/api/v1/instance/config')
        ->assertUnauthorized();
});

it('blocks company users from instance config endpoints', function () {
    $user = instanceConfigCompanyUser();

    $this->actingAs($user)
        ->getJson('/api/v1/instance/config')
        ->assertForbidden();

    $this->actingAs($user)
        ->putJson('/api/v1/instance/config', ['timezone' => 'Asia/Jakarta'])
        ->assertForbidden();
});

it('allows platform admin to get config with frontend contract shape', function () {
    $admin = instanceConfigAdmin();

    $this->actingAs($admin)
        ->getJson('/api/v1/instance/config')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'instance.config.detail')
        ->assertJsonStructure([
            'data' => [
                'timezone',
                'defaultLanguage',
                'dateFormat',
                'sessionDurationHours',
                'autoLogoutExpired',
                'loginLockoutAttempts',
                'loginLockoutMinutes',
                'smtpHost',
                'smtpPort',
                'smtpEncryption',
                'smtpUsername',
                'smtpPasswordMasked',
                'smtpFromName',
                'smtpFromEmail',
            ],
            'meta',
        ])
        ->assertJsonPath('data.timezone', 'Asia/Jakarta')
        ->assertJsonPath('data.defaultLanguage', 'id');
});

it('updates config and stores smtp password encrypted', function () {
    $admin = instanceConfigAdmin();

    $this->actingAs($admin)
        ->putJson('/api/v1/instance/config', instanceConfigPayload())
        ->assertOk()
        ->assertJsonPath('message', 'instance.config.updated')
        ->assertJsonPath('data.defaultLanguage', 'en')
        ->assertJsonPath('data.loginLockoutAttempts', 4)
        ->assertJsonPath('data.smtpPasswordMasked', '*****');

    $storedPassword = instanceSettingValue('smtp_password');

    expect(instanceSettingValue('timezone'))->toBe('Asia/Makassar')
        ->and(instanceSettingValue('language'))->toBe('en')
        ->and(instanceSettingValue('lockout_attempts'))->toBe('4')
        ->and($storedPassword)->not->toBe('TopSecret123!')
        ->and(Crypt::decryptString((string) $storedPassword))->toBe('TopSecret123!');

    $this->assertDatabaseHas('activity_log', ['description' => 'instance.config.updated']);
});

it('preserves existing smtp password when payload omits or blanks it', function () {
    $admin = instanceConfigAdmin();

    $this->actingAs($admin)
        ->putJson('/api/v1/instance/config', instanceConfigPayload())
        ->assertOk();

    $storedPassword = instanceSettingValue('smtp_password');

    $this->actingAs($admin)
        ->putJson('/api/v1/instance/config', [
            'smtpHost' => 'smtp2.example.test',
            'smtpPassword' => '',
        ])
        ->assertOk()
        ->assertJsonPath('data.smtpHost', 'smtp2.example.test')
        ->assertJsonPath('data.smtpPasswordMasked', '*****');

    expect(instanceSettingValue('smtp_password'))->toBe($storedPassword);
});

it('queues smtp test email for platform admin', function () {
    Queue::fake();
    $admin = instanceConfigAdmin();
    seedInstanceSmtpConfig();

    $this->actingAs($admin)
        ->postJson('/api/v1/instance/config/test-smtp')
        ->assertOk()
        ->assertJsonPath('message', 'instance.config.test_smtp_queued');

    Queue::assertPushed(
        SendPlatformConfigTestSmtpJob::class,
        fn (SendPlatformConfigTestSmtpJob $job): bool => $job->userId === $admin->id
    );
    $this->assertDatabaseHas('activity_log', ['description' => 'instance.config.test_smtp_queued']);
});

it('returns failure when smtp config is incomplete', function () {
    Queue::fake();
    $admin = instanceConfigAdmin();

    $this->actingAs($admin)
        ->postJson('/api/v1/instance/config/test-smtp')
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'instance.config.smtp_incomplete');

    Queue::assertNotPushed(SendPlatformConfigTestSmtpJob::class);
});

it('marks smtp test job as queued with retry backoff', function () {
    $job = new SendPlatformConfigTestSmtpJob(1);

    expect($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job->backoff())->toBe([30, 60, 120]);
});

it('sends smtp test mail to platform admin', function () {
    Mail::fake();
    $admin = instanceConfigAdmin();
    seedInstanceSmtpConfig();

    (new SendPlatformConfigTestSmtpJob((int) $admin->id))->handle(app(InstanceSettingsRepositoryInterface::class));

    Mail::assertSent(
        PlatformConfigTestSmtpMail::class,
        fn (PlatformConfigTestSmtpMail $mail): bool => $mail->hasTo((string) $admin->email)
    );
});

function instanceConfigAdmin(): User
{
    return User::withoutCompanyScope()->create([
        'company_id' => null,
        'name' => 'Platform Administrator',
        'email' => uniqid('platform.config.', true).'@example.test',
        'password' => 'password',
    ]);
}

function instanceConfigCompanyUser(): User
{
    $company = Company::create(['name' => uniqid('PT Config ', false), 'legal_name' => 'PT Config Legal']);

    return User::create([
        'company_id' => $company->id,
        'name' => 'Company User',
        'email' => uniqid('company.config.', true).'@example.test',
        'password' => 'password',
    ]);
}

/**
 * @return array<string, mixed>
 */
function instanceConfigPayload(): array
{
    return [
        'timezone' => 'Asia/Makassar',
        'defaultLanguage' => 'en',
        'dateFormat' => 'YYYY-MM-DD',
        'sessionDurationHours' => 12,
        'autoLogoutExpired' => false,
        'loginLockoutAttempts' => 4,
        'loginLockoutMinutes' => 30,
        'smtpHost' => 'smtp.example.test',
        'smtpPort' => 2525,
        'smtpEncryption' => 'tls',
        'smtpUsername' => 'mailer',
        'smtpPassword' => 'TopSecret123!',
        'smtpFromName' => 'Dictive HR',
        'smtpFromEmail' => 'no-reply@example.test',
    ];
}

function seedInstanceSmtpConfig(): void
{
    $settings = [
        'smtp_host' => 'smtp.example.test',
        'smtp_port' => '2525',
        'smtp_encryption' => 'tls',
        'smtp_username' => 'mailer',
        'smtp_password' => Crypt::encryptString('TopSecret123!'),
        'smtp_from_name' => 'Dictive HR',
        'smtp_from_email' => 'no-reply@example.test',
    ];

    foreach ($settings as $key => $value) {
        InstanceSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}

function instanceSettingValue(string $key): ?string
{
    return InstanceSetting::query()
        ->where('key', $key)
        ->value('value');
}
