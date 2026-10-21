<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\UnlockAccount;
use App\Mail\AccountLocked;
use App\Models\User;
use App\Support\AccountLockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class AccountLockoutTest extends TestCase
{
    use RefreshDatabase;

    private const string EMAIL = 'duena@gmail.com';

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->owner = User::factory()->admin()->create(['email' => self::EMAIL]);
    }

    public function test_the_second_wrong_password_warns_that_one_try_is_left(): void
    {
        $this->signIn('wrong')->assertHasErrors('data.email');

        $this->signIn('wrong')
            ->assertHasErrors('data.email')
            ->assertSee('Te queda 1 intento antes de que bloqueemos la cuenta');

        $this->assertFalse($this->owner->fresh()->isLocked());
        Mail::assertNothingSent();
    }

    public function test_the_third_wrong_password_locks_the_account_and_emails_a_code(): void
    {
        $this->signIn('wrong');
        $this->signIn('wrong');

        $this->signIn('wrong')->assertRedirect(UnlockAccount::getUrl());

        $this->assertTrue($this->owner->fresh()->isLocked());
        $this->assertSame(self::EMAIL, session(AccountLockout::SESSION_KEY));
        Mail::assertSent(AccountLocked::class, fn (AccountLocked $mail): bool => $mail->hasTo(self::EMAIL)
            && preg_match('/^\d{6}$/', $mail->code) === 1);
    }

    public function test_a_locked_account_cannot_sign_in_even_with_the_right_password(): void
    {
        $this->lockOwner();

        $this->signIn('password')->assertRedirect(UnlockAccount::getUrl());

        $this->assertGuest();
    }

    public function test_signing_in_resets_the_count_of_wrong_passwords(): void
    {
        $this->signIn('wrong');
        $this->signIn('wrong');

        $this->signIn('password')->assertHasNoErrors();

        $this->assertAuthenticatedAs($this->owner);
        $this->assertSame(0, $this->owner->fresh()->failed_login_attempts);
    }

    public function test_wrong_passwords_for_an_unknown_email_show_the_usual_error(): void
    {
        foreach (range(1, 3) as $try) {
            Livewire::test(Login::class)
                ->set('data.email', 'nadie@gmail.com')
                ->set('data.password', 'wrong')
                ->call('authenticate')
                ->assertHasErrors('data.email')
                ->assertNoRedirect();
        }

        Mail::assertNothingSent();
    }

    public function test_the_emailed_code_unlocks_the_account(): void
    {
        $code = $this->lockOwner();

        $this->unlockPage()
            ->assertSee('d•••••@gmail.com')
            ->set('data.code', $code)
            ->call('unlock')
            ->assertHasNoErrors()
            ->assertRedirect(filament()->getLoginUrl());

        $this->assertFalse($this->owner->fresh()->isLocked());
        $this->assertNull(session(AccountLockout::SESSION_KEY));
        Livewire::test(Login::class)->assertSee('Cuenta desbloqueada. Ya puedes entrar con tu contraseña.');
        $this->signIn('password');
        $this->assertAuthenticatedAs($this->owner);
    }

    public function test_a_wrong_code_says_how_many_tries_are_left(): void
    {
        $code = $this->lockOwner();

        $this->unlockPage()
            ->set('data.code', $this->otherCode($code))
            ->call('unlock')
            ->assertHasErrors('data.code')
            ->assertSee('Te quedan 4 intentos');

        $this->assertTrue($this->owner->fresh()->isLocked());
    }

    public function test_too_many_wrong_codes_void_the_code(): void
    {
        $code = $this->lockOwner();
        $page = $this->unlockPage();

        foreach (range(1, AccountLockout::MAX_CODE_ATTEMPTS) as $try) {
            $page->set('data.code', $this->otherCode($code))->call('unlock');
        }

        $page->assertSee('demasiadas veces')
            ->set('data.code', $code)
            ->call('unlock')
            ->assertSee('El código venció');

        $this->assertTrue($this->owner->fresh()->isLocked());
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $code = $this->lockOwner();
        $this->travel(AccountLockout::CODE_MINUTES + 1)->minutes();

        $this->unlockPage()
            ->set('data.code', $code)
            ->call('unlock')
            ->assertHasErrors('data.code')
            ->assertSee('El código venció');
    }

    public function test_a_new_code_can_be_requested_after_a_minute(): void
    {
        $this->freezeTime();
        $this->lockOwner();

        $this->unlockPage()->callAction('resendCode')->assertNotified('Espera 60 segundos para pedir otro código.');
        Mail::assertSentCount(1);

        $this->travel(AccountLockout::RESEND_AFTER_SECONDS)->seconds();
        $this->unlockPage()->callAction('resendCode')->assertNotified('Te enviamos un código nuevo');

        Mail::assertSentCount(2);
    }

    public function test_the_unlock_page_sends_back_to_login_when_nothing_is_locked(): void
    {
        Livewire::test(UnlockAccount::class)->assertRedirect(filament()->getLoginUrl());
    }

    public function test_the_command_unlocks_an_account_without_the_code(): void
    {
        $this->lockOwner();

        $this->artisan('hade:unlock-account', ['email' => self::EMAIL])
            ->expectsOutputToContain('desbloqueada')
            ->assertSuccessful();

        $this->assertFalse($this->owner->fresh()->isLocked());
    }

    private function signIn(string $password): Testable
    {
        return Livewire::test(Login::class)
            ->set('data.email', self::EMAIL)
            ->set('data.password', $password)
            ->call('authenticate');
    }

    /**
     * Locks the owner's account the way the login does and returns the emailed code.
     */
    private function lockOwner(): string
    {
        foreach (range(1, AccountLockout::MAX_FAILED_LOGINS) as $try) {
            $this->signIn('wrong');
        }

        $code = '';
        Mail::assertSent(AccountLocked::class, function (AccountLocked $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        return $code;
    }

    private function unlockPage(): Testable
    {
        return Livewire::test(UnlockAccount::class);
    }

    private function otherCode(string $code): string
    {
        return str_pad((string) (((int) $code + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);
    }
}
