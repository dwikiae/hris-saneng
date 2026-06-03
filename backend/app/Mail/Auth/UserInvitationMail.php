<?php

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $userName,
        public readonly string $invitationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Undangan Akses Dictive-HR');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.user-invitation',
            with: [
                'userName' => $this->userName,
                'invitationUrl' => $this->invitationUrl,
            ],
        );
    }
}
