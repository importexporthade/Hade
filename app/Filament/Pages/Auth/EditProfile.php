<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Components\Component;

/**
 * Filament's profile page without its two-step verification switches: the emailed login code is
 * always on for IT admins and off for the store owner, so there is nothing to turn on or off.
 */
class EditProfile extends BaseEditProfile
{
    public function getMultiFactorAuthenticationContentComponent(): ?Component
    {
        return null;
    }
}
