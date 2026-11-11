<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerSessionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->admin()->create(['email' => 'duena@gmail.com']);
    }

    public function test_signing_in_after_the_session_ran_out_mid_save_opens_the_manager_home(): void
    {
        session(['url.intended' => url('/livewire-de1460f5/update')]);

        $this->signIn()->assertRedirect(Filament::getUrl());
    }

    public function test_signing_in_opens_the_page_she_was_trying_to_reach(): void
    {
        session(['url.intended' => url('/manager/products')]);

        $this->signIn()->assertRedirect(url('/manager/products'));
    }

    public function test_opening_the_login_while_signed_in_never_lands_on_a_livewire_address(): void
    {
        session(['url.intended' => url('/livewire-de1460f5/update')]);

        $this->actingAs($this->owner);

        Livewire::test(Login::class)->assertRedirect(Filament::getUrl());
    }

    public function test_a_signed_out_request_from_a_manager_page_does_not_leave_its_address_to_return_to(): void
    {
        Route::post('/livewire-test/update', fn () => throw new AuthenticationException(redirectTo: '/manager/login'))->middleware('web');
        Route::get('/manager/test-page', fn () => throw new AuthenticationException(redirectTo: '/manager/login'))->middleware('web');

        $this->post('/livewire-test/update', headers: ['X-Livewire' => '1'])
            ->assertRedirect('/manager/login');
        $this->assertNull(session('url.intended'));

        // A real page is still remembered, so signing in brings her back to it
        $this->get('/manager/test-page')->assertRedirect('/manager/login');
        $this->assertSame(url('/manager/test-page'), session('url.intended'));
    }

    public function test_an_open_manager_page_keeps_the_session_alive(): void
    {
        $this->actingAs($this->owner)
            ->getJson(route('manager.keep-alive'))
            ->assertNoContent();
    }

    public function test_a_page_whose_session_ended_is_told_so_without_being_sent_to_the_login(): void
    {
        $this->getJson(route('manager.keep-alive'))->assertUnauthorized();

        $this->assertNull(session('url.intended'));
    }

    public function test_manager_pages_check_in_while_the_owner_is_signed_in(): void
    {
        $this->actingAs($this->owner)
            ->get(Filament::getUrl())
            ->assertOk()
            ->assertSee('sigue-conectada');
    }

    private function signIn(): Testable
    {
        return Livewire::test(Login::class)
            ->set('data.email', 'duena@gmail.com')
            ->set('data.password', 'password')
            ->call('authenticate');
    }
}
