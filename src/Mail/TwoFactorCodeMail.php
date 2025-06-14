<?php

namespace AuroraWebSoftware\FilamentLoginKit\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public string $code)
    {
        //
        ds(5);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        ds(6);

        return new Envelope(
            subject: __('Your security code for :app', ['app' => config('app.name')]),
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        ds(7);

        return [];
    }
}
