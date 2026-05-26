<?php

namespace App\Jobs\Employee;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendChatterMentionNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly int $chatterMessageId,
        private readonly int $mentionedUserId
    ) {}

    public function handle(): void
    {
        Log::info('employee.chatter_mention_notification', [
            'chatter_message_id' => $this->chatterMessageId,
            'mentioned_user_id' => $this->mentionedUserId,
        ]);
    }
}
