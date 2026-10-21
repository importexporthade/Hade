<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AccountLockout;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('hade:unlock-account {email}')]
#[Description('Desbloquea una cuenta del panel manager sin el código del correo')]
class UnlockAccountCommand extends Command
{
    /**
     * Execute the console command.
     *
     * The way back in when the unlock email does not arrive, for whoever runs the server.
     */
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('No hay ninguna cuenta con ese correo.');

            return self::FAILURE;
        }

        if (! $user->isLocked()) {
            $this->info("La cuenta {$user->email} no está bloqueada.");

            return self::SUCCESS;
        }

        AccountLockout::unlock($user);

        $this->info("Cuenta {$user->email} desbloqueada. Ya puede entrar a /manager.");

        return self::SUCCESS;
    }
}
