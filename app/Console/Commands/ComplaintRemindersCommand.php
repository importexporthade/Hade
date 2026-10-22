<?php

namespace App\Console\Commands;

use App\Support\ComplaintAlerts;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('hade:complaint-reminders')]
#[Description('Avisa por correo a la dueña de las hojas de reclamación cuyo plazo vence pronto o ya venció')]
class ComplaintRemindersCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sent = ComplaintAlerts::sendDue();

        $this->info($sent === 1 ? 'Se envió 1 aviso.' : "Se enviaron {$sent} avisos.");

        return self::SUCCESS;
    }
}
