<?php

namespace App\Mail;

use App\Models\Complaint;
use App\Models\StoreSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The store's answer to a complaint sheet, sent from the manager.
 */
class ComplaintAnswered extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Complaint $complaint) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Respuesta a tu hoja de reclamación N° {$this->complaint->code()} - ".StoreSetting::current()->store_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.complaint-answered',
            with: ['settings' => StoreSetting::current()],
        );
    }
}
