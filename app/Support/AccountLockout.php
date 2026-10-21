<?php

namespace App\Support;

use App\Enums\UnlockAttempt;
use App\Mail\AccountLocked;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Locks an account after repeated wrong passwords and unlocks it with a one-time code sent to
 * its email, so guessing the password stops at a few tries while the owner can always get
 * back in on her own.
 */
final class AccountLockout
{
    public const int MAX_FAILED_LOGINS = 3;

    public const int CODE_MINUTES = 15;

    public const int MAX_CODE_ATTEMPTS = 5;

    public const int RESEND_AFTER_SECONDS = 60;

    /**
     * Where the login page leaves the locked account's email for the unlock page.
     */
    public const string SESSION_KEY = 'account_unlock_email';

    /**
     * Counts a wrong password and, on the last allowed one, locks the account and emails the
     * code to unlock it.
     *
     * @return bool Whether the account is locked now.
     */
    public static function recordFailedLogin(User $user): bool
    {
        if ($user->isLocked()) {
            return true;
        }

        $user->increment('failed_login_attempts');

        if ($user->failed_login_attempts < self::MAX_FAILED_LOGINS) {
            return false;
        }

        $user->forceFill(['locked_at' => now()])->save();
        self::sendCode($user);

        return true;
    }

    public static function attemptsLeft(User $user): int
    {
        return max(0, self::MAX_FAILED_LOGINS - $user->failed_login_attempts);
    }

    public static function clearFailedLogins(User $user): void
    {
        if ($user->failed_login_attempts > 0) {
            $user->forceFill(['failed_login_attempts' => 0])->save();
        }
    }

    /**
     * Emails a fresh code, replacing any earlier one.
     */
    public static function sendCode(User $user): void
    {
        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'unlock_code' => $code,
            'unlock_code_sent_at' => now(),
            'unlock_code_attempts' => 0,
        ])->save();

        Mail::to($user->email)->send(new AccountLocked($user, $code));
    }

    /**
     * How long until another code can be sent, so the button cannot flood the inbox.
     */
    public static function secondsUntilResend(User $user): int
    {
        if ($user->unlock_code_sent_at === null) {
            return 0;
        }

        return max(0, (int) ceil(now()->diffInSeconds($user->unlock_code_sent_at->copy()->addSeconds(self::RESEND_AFTER_SECONDS), false)));
    }

    public static function attemptUnlock(User $user, string $code): UnlockAttempt
    {
        if (! $user->isLocked()) {
            return UnlockAttempt::Unlocked;
        }

        if ($user->unlock_code === null || $user->unlock_code_sent_at->copy()->addMinutes(self::CODE_MINUTES)->isPast()) {
            return UnlockAttempt::Expired;
        }

        if (! Hash::check($code, $user->unlock_code)) {
            $user->increment('unlock_code_attempts');

            if ($user->unlock_code_attempts >= self::MAX_CODE_ATTEMPTS) {
                $user->forceFill(['unlock_code' => null])->save();

                return UnlockAttempt::TooManyAttempts;
            }

            return UnlockAttempt::WrongCode;
        }

        self::unlock($user);

        return UnlockAttempt::Unlocked;
    }

    public static function codeAttemptsLeft(User $user): int
    {
        return max(0, self::MAX_CODE_ATTEMPTS - $user->unlock_code_attempts);
    }

    public static function unlock(User $user): void
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_at' => null,
            'unlock_code' => null,
            'unlock_code_sent_at' => null,
            'unlock_code_attempts' => 0,
        ])->save();
    }

    /**
     * The email with most of its name hidden, e.g. "r•••••@gmail.com", so the unlock page
     * says where the code went without spelling out the address.
     */
    public static function maskedEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($name, 0, 1).'•••••@'.$domain;
    }
}
