<?php

namespace App\Support;

use App\Models\Complaint;
use App\Models\StoreSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A sheet of the complaints book as a printable PDF, laid out like the official form, for
 * when INDECOPI or the customer asks for a copy.
 */
final class ComplaintSheetPdf
{
    public static function render(Complaint $complaint): string
    {
        return Pdf::loadView('pdf.complaint', [
            'complaint' => $complaint,
            'settings' => StoreSetting::current(),
        ])->setPaper('a4')->output();
    }

    public static function download(Complaint $complaint): StreamedResponse
    {
        return response()->streamDownload(
            function () use ($complaint): void {
                echo self::render($complaint);
            },
            self::filename($complaint),
            ['Content-Type' => 'application/pdf'],
        );
    }

    public static function filename(Complaint $complaint): string
    {
        return "hoja-de-reclamacion-{$complaint->code()}.pdf";
    }
}
