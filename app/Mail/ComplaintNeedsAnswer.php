<?php

namespace App\Mail;

use App\Enums\ComplaintAlert;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the owner a sheet is waiting for her answer: when it comes in, and as the deadline nears.
 */
class ComplaintNeedsAnswer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Complaint $complaint, public ComplaintAlert $alert) {}

    public function envelope(): Envelope
    {
        $code = $this->complaint->code();

        return new Envelope(
            subject: match ($this->alert) {
                ComplaintAlert::Filed => "Nueva hoja de reclamación N° {$code}: responde hasta el {$this->deadline()}",
                ComplaintAlert::DueSoon => "Quedan {$this->daysLeft()} días para responder la hoja N° {$code}",
                ComplaintAlert::DueToday => "Hoy vence el plazo para responder la hoja N° {$code}",
                ComplaintAlert::Overdue => "Venció el plazo de la hoja N° {$code}: respóndela cuanto antes",
            },
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.complaint-needs-answer',
            with: [
                'deadline' => $this->deadline(),
                'daysLeft' => $this->daysLeft(),
                'url' => ComplaintResource::getUrl('view', ['record' => $this->complaint], panel: 'manager'),
            ],
        );
    }

    private function deadline(): string
    {
        return $this->complaint->responseDueDate()->format('d/m/Y');
    }

    private function daysLeft(): int
    {
        return (int) today()->diffInDays($this->complaint->responseDueDate());
    }
}
