<?php

namespace App\Mail;

use App\Models\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoucherMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Voucher $voucher,
        public string $recipientName,
        public string $personalMessage = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🎁 Voucher {$this->voucher->plan->name} — TOKOKU",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.voucher',
            with: [
                'voucher' => $this->voucher,
                'plan' => $this->voucher->plan,
                'recipientName' => $this->recipientName,
                'personalMessage' => $this->personalMessage,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
