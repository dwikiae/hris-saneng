<?php

use App\Jobs\Notification\SendNotificationEmailJob;
use App\Mail\Notification\GenericNotificationMail;
use App\Models\Company;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('uses queued notification retry convention', function () {
    $job = new SendNotificationEmailJob(1);

    expect($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job->tries)->toBe(3)
        ->and($job->backoff())->toBe([30, 60, 120]);
});

it('sends notification email only from the queued job handler', function () {
    Mail::fake();

    $company = Company::query()->create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    $user = User::query()->create([
        'company_id' => $company->id,
        'name' => 'Notification User',
        'email' => 'notification.user@example.test',
        'password' => 'password',
    ]);
    $notification = Notification::query()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'type' => 'system',
        'title_key' => 'notification.mail_subject',
        'body_key' => 'notification.mail_body',
        'data' => [],
    ]);

    (new SendNotificationEmailJob((int) $notification->id))->handle();

    Mail::assertSent(GenericNotificationMail::class, function (GenericNotificationMail $mail) use ($user): bool {
        return $mail->hasTo((string) $user->email);
    });
});

it('logs failed notification handling without sensitive exception message', function () {
    Log::spy();

    (new SendNotificationEmailJob(99))->failed(new RuntimeException('smtp password secret'));

    Log::shouldHaveReceived('error')
        ->once()
        ->with('notification.email_failed', [
            'notification_id' => 99,
            'exception_class' => RuntimeException::class,
        ]);
});
