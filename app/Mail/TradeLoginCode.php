<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TradeLoginCode extends Mailable
{
    public function __construct(public readonly string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your SABExistCount Trades code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trade-login-code',
            with: [
                'code' => $this->code,
                'purpose' => 'login',
            ],
        );
    }
}
