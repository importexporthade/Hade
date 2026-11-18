<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'unlock_code'])]
class User extends Authenticatable implements FilamentUser, HasEmailAuthentication
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'locked_at' => 'datetime',
            'unlock_code' => 'hashed',
            'unlock_code_sent_at' => 'datetime',
        ];
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    /**
     * Only the store owner's account opens the manager, so any account added later for
     * customers stays out of it.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    /**
     * A manager account of the IT team, listed by email in IT_ADMIN_EMAILS: it opens the IT
     * Admin page and confirms every login with an emailed code.
     */
    public function isItAdmin(): bool
    {
        return $this->is_admin && in_array(strtolower((string) $this->email), config('auth.it_admin_emails'), true);
    }

    /**
     * Always on for IT admins and off for everyone else, so it cannot be switched from the profile.
     */
    public function hasEmailAuthentication(): bool
    {
        return $this->isItAdmin();
    }

    public function toggleEmailAuthentication(bool $condition): void {}
}
