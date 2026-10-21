<?php

namespace App\Filament\Pages\Auth;

use App\Enums\UnlockAttempt;
use App\Models\User;
use App\Support\AccountLockout;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

/**
 * Where a locked account lands: the owner types the code emailed to her and can sign in again.
 *
 * @property-read Schema $form
 */
class UnlockAccount extends SimplePage
{
    use WithRateLimiting;

    /**
     * How many codes one account can ask for in an hour, on top of the wait between them.
     */
    private const int MAX_CODES_PER_HOUR = 5;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    #[Locked]
    public string $email = '';

    public static function getUrl(): string
    {
        return route('filament.'.Filament::getCurrentOrDefaultPanel()->getId().'.auth.unlock');
    }

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());

            return;
        }

        $this->email = (string) session(AccountLockout::SESSION_KEY);

        if (! $this->lockedUser()) {
            $this->redirect(filament()->getLoginUrl());

            return;
        }

        $this->form->fill();
    }

    public function unlock(): void
    {
        try {
            $this->rateLimit(10);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title("Demasiados intentos. Espera {$exception->secondsUntilAvailable} segundos.")
                ->danger()
                ->send();

            return;
        }

        $code = $this->form->getState()['code'];
        $user = $this->lockedUser();

        if ($user === null) {
            $this->redirect(filament()->getLoginUrl());

            return;
        }

        $attempt = AccountLockout::attemptUnlock($user, $code);

        if ($attempt !== UnlockAttempt::Unlocked) {
            throw ValidationException::withMessages([
                'data.code' => $attempt->error(AccountLockout::codeAttemptsLeft($user)),
            ]);
        }

        session()->forget(AccountLockout::SESSION_KEY);
        session()->flash(Login::JUST_UNLOCKED_SESSION_KEY, true);

        $this->redirect(filament()->getLoginUrl());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('code')
                    ->label('Código de desbloqueo')
                    ->placeholder('000000')
                    ->required()
                    ->rule('digits:6')
                    ->validationAttribute('código')
                    ->autocomplete('one-time-code')
                    ->extraInputAttributes(['inputmode' => 'numeric', 'maxlength' => 6, 'class' => 'text-center tracking-[0.5em]'])
                    ->autofocus(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('unlock')
                    ->footer([
                        Actions::make([
                            Action::make('unlock')
                                ->label('Desbloquear')
                                ->submit('unlock'),
                        ])->fullWidth(),
                    ]),
                Actions::make([$this->resendCodeAction, $this->loginAction])->alignment(Alignment::Center),
            ]);
    }

    public function resendCodeAction(): Action
    {
        return Action::make('resendCode')
            ->link()
            ->label('¿No llegó? Reenviar código')
            ->icon(Heroicon::OutlinedEnvelope)
            ->action(function (): void {
                $user = $this->lockedUser();

                if ($user === null) {
                    $this->redirect(filament()->getLoginUrl());

                    return;
                }

                $wait = AccountLockout::secondsUntilResend($user);
                $limiterKey = "unlock-code:{$user->id}";

                if ($wait > 0 || RateLimiter::tooManyAttempts($limiterKey, self::MAX_CODES_PER_HOUR)) {
                    Notification::make()
                        ->title($wait > 0 ? "Espera {$wait} segundos para pedir otro código." : 'Ya pediste varios códigos. Inténtalo en una hora.')
                        ->warning()
                        ->send();

                    return;
                }

                RateLimiter::hit($limiterKey, 3600);
                AccountLockout::sendCode($user);

                Notification::make()
                    ->title('Te enviamos un código nuevo')
                    ->body('Revisa '.AccountLockout::maskedEmail($user->email).', también en la carpeta de spam.')
                    ->success()
                    ->send();
            });
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label('Volver a iniciar sesión')
            ->icon(Heroicon::ArrowLeft)
            ->url(filament()->getLoginUrl());
    }

    public function getTitle(): string|Htmlable
    {
        return 'Desbloquear cuenta';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Desbloquea tu cuenta';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Por seguridad bloqueamos la cuenta después de '.AccountLockout::MAX_FAILED_LOGINS.' intentos fallidos. '
            .'Escribe el código de 6 dígitos que enviamos a '.AccountLockout::maskedEmail($this->email).'.';
    }

    private function lockedUser(): ?User
    {
        if ($this->email === '') {
            return null;
        }

        $user = User::query()->where('email', $this->email)->first();

        return $user?->isLocked() ? $user : null;
    }
}
