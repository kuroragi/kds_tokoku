<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $details;

    /**
     * Create a new message instance.
     * 
     * $details should contain:
     * - subject: Email subject
     * - userName: User name
     * - mode: 'otp' or 'url'
     * - otpCode: (for OTP mode) 6-digit code
     * - verificationUrl: (for URL mode) Signed verification URL
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->details['subject'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $mode = $this->details['mode'] ?? 'otp';

        if ($mode === 'url') {
            return new Content(
                view: 'mails.verify-email',
                with: [
                    'userName' => $this->details['userName'],
                    'verificationUrl' => $this->details['verificationUrl'],
                ],
            );
        }

        return new Content(
            view: 'mails.verify-otp',
            with: [
                'userName' => $this->details['userName'],
                'otpCode' => $this->details['otpCode'],
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
