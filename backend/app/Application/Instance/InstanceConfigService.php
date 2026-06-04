<?php

namespace App\Application\Instance;

use App\Jobs\Instance\SendPlatformConfigTestSmtpJob;
use App\Models\User;
use App\Repositories\Contracts\InstanceSettingsRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class InstanceConfigService
{
    /**
     * @var array<string, string>
     */
    private array $payloadKeys = [
        'timezone' => 'timezone',
        'defaultLanguage' => 'language',
        'dateFormat' => 'date_format',
        'sessionDurationHours' => 'session_duration_hours',
        'autoLogoutExpired' => 'auto_logout',
        'loginLockoutAttempts' => 'lockout_attempts',
        'loginLockoutMinutes' => 'lockout_duration_minutes',
        'smtpHost' => 'smtp_host',
        'smtpPort' => 'smtp_port',
        'smtpEncryption' => 'smtp_encryption',
        'smtpUsername' => 'smtp_username',
        'smtpFromName' => 'smtp_from_name',
        'smtpFromEmail' => 'smtp_from_email',
    ];

    public function __construct(private readonly InstanceSettingsRepositoryInterface $settings) {}

    /**
     * @return array<string, mixed>
     */
    public function current(): array
    {
        return $this->settings->getAll()->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function update(array $payload, User $actor): array
    {
        $data = [];

        foreach ($this->payloadKeys as $source => $target) {
            if (array_key_exists($source, $payload)) {
                $data[$target] = $payload[$source];
            }
        }

        if (array_key_exists('smtpPassword', $payload) && filled($payload['smtpPassword'])) {
            $data['smtp_password'] = Crypt::encryptString((string) $payload['smtpPassword']);
        }

        $this->settings->setMany($data);

        activity()
            ->useLog('instance_config')
            ->causedBy($actor)
            ->event('updated')
            ->withProperties([
                'changed_keys' => array_values(array_filter(array_keys($data), fn (string $key): bool => $key !== 'smtp_password')),
                'smtp_password' => array_key_exists('smtp_password', $data) ? '[REDACTED]' : null,
            ])
            ->log('instance.config.updated');

        return $this->current();
    }

    public function dispatchTestSmtp(User $actor): void
    {
        $config = $this->settings->getAll();

        if (! $this->canSendTest($config, $actor)) {
            throw new RuntimeException('instance.config.smtp_incomplete');
        }

        SendPlatformConfigTestSmtpJob::dispatch((int) $actor->getKey());

        activity()
            ->useLog('instance_config')
            ->causedBy($actor)
            ->event('test_smtp_queued')
            ->log('instance.config.test_smtp_queued');
    }

    private function canSendTest(Collection $config, User $actor): bool
    {
        return filled($actor->email)
            && filled($config->get('smtp_host'))
            && filled($config->get('smtp_port'))
            && filled($config->get('smtp_from_email'))
            && filled($config->get('smtp_from_name'));
    }
}
