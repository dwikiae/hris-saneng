<?php

namespace App\Application\Notification;

use App\Jobs\Notification\SendNotificationEmailJob;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class NotificationService
{
    /**
     * @var array<int, string>
     */
    private const SENSITIVE_DATA_KEYS = [
        'password',
        'token',
        'nik',
        'npwp',
        'rekening',
        'bank_account',
        'salary',
        'allowance',
        'deduction',
    ];

    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createInAppNotification(
        User $user,
        string $type,
        string $titleKey,
        string $bodyKey,
        array $data = [],
        ?int $createdBy = null
    ): Notification {
        return $this->notifications->createForUser($user, [
            'type' => $type,
            'title_key' => $titleKey,
            'body_key' => $bodyKey,
            'data' => $this->safeData($data),
            'created_by' => $createdBy,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function notifyUser(
        User $user,
        string $type,
        string $titleKey,
        string $bodyKey,
        array $data = [],
        bool $queueEmail = false,
        ?int $createdBy = null
    ): Notification {
        $notification = $this->createInAppNotification($user, $type, $titleKey, $bodyKey, $data, $createdBy);

        if ($queueEmail) {
            SendNotificationEmailJob::dispatch((int) $notification->getKey());
        }

        return $notification;
    }

    public function alertInstanceAdminsForFailedNotification(int $notificationId, Throwable $exception): void
    {
        Log::error('notification.email_failed', [
            'notification_id' => $notificationId,
            'exception_class' => $exception::class,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function safeData(array $data): array
    {
        $safe = [];

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if ($this->isSensitiveKey($normalizedKey)) {
                $safe[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $safe[$key] = $this->safeData($value);

                continue;
            }

            if (! is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException('notification.data_must_be_scalar');
            }

            $safe[$key] = $value;
        }

        return $safe;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_DATA_KEYS as $sensitiveKey) {
            if (str_contains($key, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }
}
