<?php

namespace Tests\Feature;

use App\Enums\ComplaintAlert;
use App\Mail\ComplaintNeedsAnswer;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * A sheet filed on Monday 5 October 2026 must be answered by Tuesday 20 October (fifteen
 * calendar days); the "due soon" reminder goes out three days earlier, on the 17th.
 */
class ComplaintAlertsTest extends TestCase
{
    use RefreshDatabase;

    private const string OWNER = 'duena@gmail.com';

    private Complaint $complaint;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        User::factory()->admin()->create(['email' => self::OWNER]);

        $this->travelTo('2026-10-05 10:00:00');
        $this->complaint = Complaint::factory()->create(['sequence' => 3, 'last_alert' => ComplaintAlert::Filed]);
    }

    public function test_nothing_is_sent_while_the_deadline_is_far(): void
    {
        $this->travelTo('2026-10-16 08:00:00');

        $this->artisan('hade:complaint-reminders')->expectsOutput('Se enviaron 0 avisos.')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_the_owner_is_reminded_three_days_before_the_deadline(): void
    {
        $this->travelTo('2026-10-17 08:00:00');

        $this->artisan('hade:complaint-reminders')->expectsOutput('Se envió 1 aviso.');

        Mail::assertSent(ComplaintNeedsAnswer::class, fn (ComplaintNeedsAnswer $mail): bool => $mail->hasTo(self::OWNER)
            && $mail->alert === ComplaintAlert::DueSoon
            && $mail->hasSubject('Quedan 3 días para responder la hoja N° 000003-2026'));
        $this->assertSame(ComplaintAlert::DueSoon, $this->complaint->fresh()->last_alert);
    }

    public function test_each_reminder_goes_out_once_even_if_the_task_runs_again(): void
    {
        $this->travelTo('2026-10-17 08:00:00');
        $this->artisan('hade:complaint-reminders');
        $this->artisan('hade:complaint-reminders');
        $this->travelTo('2026-10-18 08:00:00');
        $this->artisan('hade:complaint-reminders');

        Mail::assertSentCount(1);
    }

    public function test_the_owner_is_warned_on_the_last_day_and_once_it_is_overdue(): void
    {
        $this->travelTo('2026-10-20 08:00:00');
        $this->artisan('hade:complaint-reminders');
        $this->travelTo('2026-10-21 08:00:00');
        $this->artisan('hade:complaint-reminders');

        Mail::assertSent(ComplaintNeedsAnswer::class, fn (ComplaintNeedsAnswer $mail): bool => $mail->alert === ComplaintAlert::DueToday
            && $mail->hasSubject('Hoy vence el plazo para responder la hoja N° 000003-2026'));
        Mail::assertSent(ComplaintNeedsAnswer::class, fn (ComplaintNeedsAnswer $mail): bool => $mail->alert === ComplaintAlert::Overdue);
        Mail::assertSentCount(2);
    }

    public function test_missed_days_send_only_the_latest_reminder(): void
    {
        $this->travelTo('2026-10-30 08:00:00');

        $this->artisan('hade:complaint-reminders');

        Mail::assertSentCount(1);
        Mail::assertSent(ComplaintNeedsAnswer::class, fn (ComplaintNeedsAnswer $mail): bool => $mail->alert === ComplaintAlert::Overdue);
    }

    public function test_answered_sheets_get_no_reminders(): void
    {
        $this->complaint->respond('Te enviamos una lámpara nueva sin costo.');
        $this->travelTo('2026-10-27 08:00:00');

        $this->artisan('hade:complaint-reminders');

        Mail::assertNothingSent();
    }

    public function test_the_reminder_links_to_the_sheet_in_the_manager(): void
    {
        $rendered = (new ComplaintNeedsAnswer($this->complaint, ComplaintAlert::Overdue))->render();

        $this->assertStringContainsString('Venció el plazo', $rendered);
        $this->assertStringContainsString('/manager/complaints/'.$this->complaint->getKey(), $rendered);
        $this->assertStringContainsString('Responder en el panel', $rendered);
    }

    public function test_the_reminders_run_every_morning(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('hade:complaint-reminders');
    }
}
