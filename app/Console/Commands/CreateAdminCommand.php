<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('hade:create-admin {email} {name}')]
#[Description('Crea la cuenta que entra al panel manager')]
class CreateAdminCommand extends Command
{
    /**
     * Execute the console command.
     *
     * The password is asked interactively so it never ends up in the shell history.
     */
    public function handle(): int
    {
        $password = (string) $this->secret('Contraseña');

        $validator = Validator::make(
            ['email' => $this->argument('email'), 'name' => $this->argument('name'), 'password' => $password],
            ['email' => ['required', 'email', 'unique:users,email'], 'name' => ['required', 'string', 'max:255'], 'password' => ['required', 'string', 'min:12']],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::query()->create($validator->validated());
        $user->forceFill(['is_admin' => true])->save();

        $this->info("Cuenta {$user->email} creada. Ya puede entrar a /manager.");

        return self::SUCCESS;
    }
}
