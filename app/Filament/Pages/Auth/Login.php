<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Support\AccountLockout;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use SensitiveParameter;

/**
 * Filament's login, plus a lock on the account after too many wrong passwords. A locked account
 * is sent to the unlock page, even with the right password, until the emailed code is entered.
 */
class Login extends BaseLogin
{
    /**
     * Set by the unlock page, so the login it redirects to confirms the account is open again.
     */
    public const string JUST_UNLOCKED_SESSION_KEY = 'account_just_unlocked';

    /**
     * The account whose password was just rejected, if the email belongs to one.
     */
    private ?User $rejectedUser = null;

    #[Locked]
    public bool $justUnlocked = false;

    public function mount(): void
    {
        parent::mount();

        $this->justUnlocked = (bool) session()->pull(self::JUST_UNLOCKED_SESSION_KEY);
    }

    /**
     * Confirms the unlock under the heading, where it stays until she signs in, instead of in a
     * notification that can vanish before she reads it.
     */
    public function getSubheading(): string|Htmlable|null
    {
        if ($this->justUnlocked) {
            return new HtmlString('<span class="hade-unlocked">Cuenta desbloqueada. Ya puedes entrar con tu contraseña.</span>');
        }

        return parent::getSubheading();
    }

    public function authenticate(): ?LoginResponse
    {
        $user = User::query()->where('email', $this->data['email'] ?? null)->first();

        if ($user?->isLocked()) {
            $this->sendToUnlockPage($user);

            return null;
        }

        try {
            $response = parent::authenticate();
        } catch (ValidationException $exception) {
            if ($this->rejectedUser === null) {
                throw $exception;
            }

            if (AccountLockout::recordFailedLogin($this->rejectedUser)) {
                $this->sendToUnlockPage($this->rejectedUser);

                return null;
            }

            throw $this->withLockoutWarning($exception, $this->rejectedUser);
        }

        if ($response !== null && $user !== null) {
            AccountLockout::clearFailedLogins($user);
        }

        return $response;
    }

    protected function fireFailedEvent(Guard $guard, ?Authenticatable $user, #[SensitiveParameter] array $credentials): void
    {
        $this->rejectedUser = $user instanceof User ? $user : null;

        parent::fireFailedEvent($guard, $user, $credentials);
    }

    /**
     * Warns before the last try, so the lock never comes as a surprise.
     */
    private function withLockoutWarning(ValidationException $exception, User $user): ValidationException
    {
        if (AccountLockout::attemptsLeft($user) !== 1) {
            return $exception;
        }

        return ValidationException::withMessages([
            'data.email' => $exception->validator->errors()->first('data.email')
                .' Te queda 1 intento antes de que bloqueemos la cuenta por seguridad.',
        ]);
    }

    private function sendToUnlockPage(User $user): void
    {
        session()->put(AccountLockout::SESSION_KEY, $user->email);

        $this->redirect(UnlockAccount::getUrl());
    }
}
