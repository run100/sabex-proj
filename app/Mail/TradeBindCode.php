<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TradeBindCode extends Mailable
{
    public function __construct(public readonly string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your email for SABExistCount Trades',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trade-login-code',
            with: [
                'code' => $this->code,
                'purpose' => 'bind',
            ],
        );
    }
}
