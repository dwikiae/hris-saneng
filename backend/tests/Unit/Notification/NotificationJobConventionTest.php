<?php

use App\Jobs\Notification\SendNotificationEmailJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

it('uses notification queue retry convention without database access', function () {
    $job = new SendNotificationEmailJob(1);

    expect($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job->tries)->toBe(3)
        ->and($job->backoff())->toBe([30, 60, 120]);
});

it('traces failed notification email without logging sensitive exception text', function () {
    Log::spy();

    (new SendNotificationEmailJob(99))->failed(new RuntimeException('smtp password secret'));

    Log::shouldHaveReceived('error')
        ->once()
        ->with('notification.email_failed', [
            'notification_id' => 99,
            'exception_class' => RuntimeException::class,
        ]);
});
