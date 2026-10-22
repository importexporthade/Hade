<?php

namespace App\Support;

use App\Enums\ComplaintAlert;
use App\Mail\ComplaintNeedsAnswer;
use App\Models\Complaint;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Emails the owner when a sheet comes in and again as its deadline nears, since answering
 * late is what draws INDECOPI's fines.
 */
final class ComplaintAlerts
{
    /**
     * Sends every reminder that is due and not sent yet. Running it twice in a day, or missing
     * a day, sends each reminder exactly once.
     *
     * @return int How many reminders went out.
     */
    public static function sendDue(): int
    {
        $sent = 0;

        Complaint::query()->pending()->each(function (Complaint $complaint) use (&$sent): void {
            $alert = self::dueAlert($complaint);

            if ($alert !== null && $alert->comesAfter($complaint->last_alert)) {
                self::send($complaint, $alert);
                $sent++;
            }
        });

        return $sent;
    }

    /**
     * The latest reminder a pending sheet calls for today, if any.
     */
    public static function dueAlert(Complaint $complaint): ?ComplaintAlert
    {
        if ($complaint->isAnswered()) {
            return null;
        }

        $today = today();
        $deadline = $complaint->responseDueDate();

        return match (true) {
            $today->greaterThan($deadline) => ComplaintAlert::Overdue,
            $today->equalTo($deadline) => ComplaintAlert::DueToday,
            $today->greaterThanOrEqualTo($deadline->copy()->subDays(ComplaintAlert::DUE_SOON_DAYS)) => ComplaintAlert::DueSoon,
            default => null,
        };
    }

    /**
     * Emails the alert to the owner's manager account and records it. Addresses that already
     * got the customer's copy are skipped for the new-sheet alert, so nobody gets two emails.
     *
     * @param  list<string>  $alreadyNotified
     */
    public static function send(Complaint $complaint, ComplaintAlert $alert, array $alreadyNotified = []): void
    {
        $recipients = array_values(array_diff(self::recipients(), $alreadyNotified));

        if ($recipients !== []) {
            Mail::to($recipients)->send(new ComplaintNeedsAnswer($complaint, $alert));
        }

        $complaint->forceFill(['last_alert' => $alert])->save();
    }

    /**
     * The manager accounts, or the store's contact email when there is none.
     *
     * @return list<string>
     */
    private static function recipients(): array
    {
        $owners = User::query()->where('is_admin', true)->pluck('email')->all();

        return $owners !== [] ? $owners : array_values(array_filter([StoreSetting::current()->email]));
    }
}
