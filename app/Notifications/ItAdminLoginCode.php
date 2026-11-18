<?php

namespace App\Notifications;

use App\Models\StoreSetting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

/**
 * The 6-digit code an IT admin types after the password on every login. Sent right away, not
 * queued like Filament's own, since Hostinger runs no queue worker.
 */
class ItAdminLoginCode extends Notification
{
    public function __construct(
        #[SensitiveParameter]
        public string $code,
        public int $codeExpiryMinutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tu código para entrar al manager: {$this->code}")
            ->markdown('mail.it-admin-login-code', [
                'name' => $notifiable->name,
                'code' => $this->code,
                'codeExpiryMinutes' => $this->codeExpiryMinutes,
                'settings' => StoreSetting::current(),
            ]);
    }
}
