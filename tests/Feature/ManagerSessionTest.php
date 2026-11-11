<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
