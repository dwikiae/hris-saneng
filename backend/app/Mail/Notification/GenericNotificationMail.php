<?php

namespace App\Mail\Notification;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $titleKey,
        public readonly string $bodyKey,
        public readonly array $data = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __($this->titleKey));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notification.generic');
    }
}
