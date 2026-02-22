<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Subscription $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Paket Anda Telah Aktif - TOKOKU',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.subscription-activated',
            with: [
                'userName' => $this->subscription->user->name,
                'planName' => $this->subscription->plan->name,
                'endsAt' => $this->subscription->ends_at,
                'setupUrl' => route('onboarding.setup-instance'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
