<?php

namespace App\Console\Commands;

use App\Mail\TradeLoginCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailTestCommand extends Command
{
    protected $signature = 'mail:test {email=linuxc100@gmail.com}';

    protected $description = 'Send a Resend test using the trades login-code template';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        try {
            Mail::to($email)->send(new TradeLoginCode('000000'));
            $this->info('Sent via '.config('mail.default').' to '.$email);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
