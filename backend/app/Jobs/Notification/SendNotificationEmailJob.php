<?php

namespace App\Jobs\Notification;

use App\Core\Notification\Application\NotificationService;
use App\Mail\Notification\GenericNotificationMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendNotificationEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $notificationId) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(): void
    {
        $notification = $this->notification();
        $recipient = $notification->getRelation('user');

        if (! $recipient instanceof User) {
            return;
        }

        Mail::to((string) $recipient->email)->send(new GenericNotificationMail(
            (string) $notification->getAttribute('title_key'),
            (string) $notification->getAttribute('body_key'),
            $notification->getAttribute('data') ?? []
        ));
    }

    public function failed(Throwable $exception): void
    {
        app(NotificationService::class)->alertInstanceAdminsForFailedNotification($this->notificationId, $exception);
    }

    private function notification(): Notification
    {
        /** @var Notification $notification */
        $notification = Notification::query()
            ->withoutGlobalScope('company')
            ->with('user')
            ->findOrFail($this->notificationId);

        return $notification;
    }
}
