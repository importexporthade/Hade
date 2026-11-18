<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use App\Notifications\ItAdminLoginCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ItAdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private const string IT_EMAIL = 'it@bytebuster.example';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        // The code is kept in the cache, so use the database store production runs on
        config(['auth.it_admin_emails' => [self::IT_EMAIL], 'cache.default' => 'database']);
    }

    public function test_an_it_admin_enters_only_with_the_emailed_six_digit_code(): void
    {
        $itAdmin = User::factory()->admin()->create(['email' => self::IT_EMAIL]);

        $login = $this->signIn(self::IT_EMAIL);

        $this->assertGuest();
        $code = null;
        Notification::assertSentTo($itAdmin, ItAdminLoginCode::class, function (ItAdminLoginCode $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        });
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertStringContainsString($code, (string) (new ItAdminLoginCode($code, 10))->toMail($itAdmin)->render());

        $login->set('data.multiFactor.email_code.code', $code)
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($itAdmin);
    }

    public function test_a_wrong_code_keeps_the_it_admin_out(): void
    {
        User::factory()->admin()->create(['email' => self::IT_EMAIL]);

        $this->signIn(self::IT_EMAIL)
            ->set('data.multiFactor.email_code.code', '000000')
            ->call('authenticate')
            ->assertHasErrors('data.multiFactor.email_code.code');

        $this->assertGuest();
    }

    public function test_the_store_owner_enters_with_the_password_alone(): void
    {
        $owner = User::factory()->admin()->create(['email' => 'duena@gmail.com']);

        $this->signIn('duena@gmail.com')->assertHasNoErrors();

        $this->assertAuthenticatedAs($owner);
        Notification::assertNothingSent();
    }

    public function test_the_profile_has_no_switch_for_the_emailed_code(): void
    {
        $this->actingAs(User::factory()->admin()->create(['email' => self::IT_EMAIL]));

        $this->get('/manager/profile')
            ->assertOk()
            ->assertDontSee('Códigos de verificación por correo electrónico');
    }

    private function signIn(string $email): Testable
    {
        return Livewire::test(Login::class)
            ->set('data.email', $email)
            ->set('data.password', 'password')
            ->call('authenticate');
    }
}
