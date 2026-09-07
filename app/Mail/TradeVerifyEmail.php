<?php

namespace App\Mail;

use App\Services\Trades\EmailVerificationService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TradeVerifyEmail extends Mailable
{
    public function __construct(
        public readonly string $verifyUrl,
        public readonly string $email,
        public readonly string $intent = EmailVerificationService::INTENT_REGISTER,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your email for SABExistCount Trades',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trade-verify-email',
            with: [
                'verifyUrl' => $this->verifyUrl,
                'email' => $this->email,
                'intent' => $this->intent,
            ],
        );
    }
}
