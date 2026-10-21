<?php

namespace App\Mail;

use App\Models\StoreSetting;
use App\Models\User;
use App\Support\AccountLockout;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The code that unlocks the manager account after too many wrong passwords.
 */
class AccountLocked extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Tu código para desbloquear la cuenta: {$this->code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.account-locked',
            with: [
                'settings' => StoreSetting::current(),
                'maxFailedLogins' => AccountLockout::MAX_FAILED_LOGINS,
                'codeMinutes' => AccountLockout::CODE_MINUTES,
            ],
        );
    }
}
