<?php

namespace App\Jobs\Auth;

use App\Mail\Auth\UserInvitationMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendUserInvitationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly int $userId,
        public readonly string $token,
    ) {}

    public function handle(): void
    {
        /** @var User $user */
        $user = User::withoutCompanyScope()->findOrFail($this->userId);

        Mail::to((string) $user->getAttribute('email'))->send(new UserInvitationMail(
            (string) $user->getAttribute('name'),
            $this->url('/set-password?token='.$this->token),
        ));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/').$path;
    }
}
