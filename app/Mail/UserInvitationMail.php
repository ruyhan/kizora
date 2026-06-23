<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $url,
        public ?string $role = null,
        public ?string $inviter = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You have been invited to join Kizora');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invitation');
    }
}
