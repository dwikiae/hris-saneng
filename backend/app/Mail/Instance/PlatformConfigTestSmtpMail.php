<?php

namespace App\Mail\Instance;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlatformConfigTestSmtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('settings.test_smtp.subject'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.instance.platform-config-test-smtp');
    }
}
