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
 * The customer's copy of the complaint sheet, sent the moment it is filed.
 */
class ComplaintFiled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Complaint $complaint) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Hoja de reclamación N° {$this->complaint->code()} - ".StoreSetting::current()->store_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.complaint-filed',
            with: ['settings' => StoreSetting::current()],
        );
    }
}
