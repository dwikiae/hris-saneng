<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstanceConfigResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $config = is_array($this->resource) ? $this->resource : [];

        return [
            'timezone' => $config['timezone'] ?? 'Asia/Jakarta',
            'defaultLanguage' => $config['language'] ?? 'id',
            'dateFormat' => $config['date_format'] ?? 'DD/MM/YYYY',
            'sessionDurationHours' => (int) ($config['session_duration_hours'] ?? 8),
            'autoLogoutExpired' => (bool) ($config['auto_logout'] ?? true),
            'loginLockoutAttempts' => (int) ($config['lockout_attempts'] ?? 3),
            'loginLockoutMinutes' => (int) ($config['lockout_duration_minutes'] ?? 15),
            'smtpHost' => $config['smtp_host'] ?? null,
            'smtpPort' => $this->nullableInteger($config['smtp_port'] ?? null),
            'smtpEncryption' => $config['smtp_encryption'] ?? 'tls',
            'smtpUsername' => $config['smtp_username'] ?? null,
            'smtpPasswordMasked' => empty($config['smtp_password'] ?? null) ? null : '*****',
            'smtpFromName' => $config['smtp_from_name'] ?? null,
            'smtpFromEmail' => $config['smtp_from_email'] ?? null,
        ];
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
