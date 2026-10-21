<?php

namespace App\Enums;

/**
 * What happened when someone typed the code to unlock a locked account.
 */
enum UnlockAttempt
{
    case Unlocked;
    case WrongCode;
    case Expired;
    case TooManyAttempts;

    /**
     * The message shown under the code field, or null when the account was unlocked.
     */
    public function error(int $attemptsLeft = 0): ?string
    {
        return match ($this) {
            self::Unlocked => null,
            self::WrongCode => $attemptsLeft === 1
                ? 'El código no es correcto. Te queda 1 intento.'
                : "El código no es correcto. Te quedan {$attemptsLeft} intentos.",
            self::Expired => 'El código venció. Pide uno nuevo con «Reenviar código».',
            self::TooManyAttempts => 'Escribiste un código incorrecto demasiadas veces. Pide uno nuevo con «Reenviar código».',
        };
    }
}
