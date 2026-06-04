<?php

namespace App\Jobs\Instance;

use App\Mail\Instance\PlatformConfigTestSmtpMail;
use App\Models\User;
use App\Repositories\Contracts\InstanceSettingsRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class SendPlatformConfigTestSmtpJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $userId) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(InstanceSettingsRepositoryInterface $settings): void
    {
        $user = User::query()->withoutGlobalScope('company')->findOrFail($this->userId);
        $config = $settings->getAll();

        Config::set('mail.mailers.smtp.host', $config->get('smtp_host'));
        Config::set('mail.mailers.smtp.port', $config->get('smtp_port'));
        Config::set('mail.mailers.smtp.username', $config->get('smtp_username'));
        Config::set('mail.mailers.smtp.password', $this->smtpPassword((string) ($config->get('smtp_password') ?? '')));
        Config::set('mail.mailers.smtp.scheme', $this->scheme((string) ($config->get('smtp_encryption') ?? 'tls')));
        Config::set('mail.from.address', $config->get('smtp_from_email'));
        Config::set('mail.from.name', $config->get('smtp_from_name'));

        Mail::mailer('smtp')
            ->to((string) $user->getAttribute('email'))
            ->send(new PlatformConfigTestSmtpMail);
    }

    private function smtpPassword(string $encryptedPassword): ?string
    {
        if ($encryptedPassword === '') {
            return null;
        }

        return Crypt::decryptString($encryptedPassword);
    }

    private function scheme(string $encryption): ?string
    {
        return $encryption === 'none' ? null : $encryption;
    }
}
