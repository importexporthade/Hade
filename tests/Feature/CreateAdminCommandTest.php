<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_account_that_opens_the_manager(): void
    {
        $this->artisan('hade:create-admin', ['email' => 'duena@hade.pe', 'name' => 'Dueña'])
            ->expectsQuestion('Contraseña', 'una-clave-bien-larga')
            ->assertSuccessful();

        $user = User::query()->sole();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('una-clave-bien-larga', $user->password));
    }

    public function test_it_refuses_a_short_password(): void
    {
        $this->artisan('hade:create-admin', ['email' => 'duena@hade.pe', 'name' => 'Dueña'])
            ->expectsQuestion('Contraseña', 'corta')
            ->expectsOutputToContain('12')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
