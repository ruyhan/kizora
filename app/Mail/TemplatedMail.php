<?php

namespace App\Mail;

use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

/**
 * Generic queued mailable for DB-stored templates. Carries pre-rendered HTML +
 * plain-text, tags itself with the EmailLog id (so the MessageSent listener can
 * flip the log to "sent"), retries with backoff, and is rate-limited.
 */
class TemplatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $subjectLine,
        public string $bodyHtml,
        public string $bodyText,
        public int $logId,
        public ?string $replyToAddress = null,
        public ?string $logoPath = null,
        public ?string $logoFallback = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
            replyTo: $this->replyToAddress ? [$this->replyToAddress] : [],
        );
    }

    public function content(): Content
    {
        // Rendered through a Blade wrapper so the logo placeholder can be swapped
        // for an inline CID (via $message->embed) — which renders in Gmail too.
        return new Content(
            view: 'emails.wrapper',
            text: 'emails.plain',
            with: [
                'html'         => $this->bodyHtml,
                'logoPath'     => $this->logoPath,
                'logoFallback' => $this->logoFallback,
                'text'         => $this->bodyText,
            ],
        );
    }

    public function headers(): Headers
    {
        return new Headers(text: ['X-Kizora-Log' => (string) $this->logId]);
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function middleware(): array
    {
        return [new RateLimited('emails')];
    }

    /** Queue gave up after all retries → mark the log failed. */
    public function failed(\Throwable $e): void
    {
        EmailLog::where('id', $this->logId)->update([
            'status' => 'failed',
            'error'  => $e->getMessage(),
        ]);
    }
}
