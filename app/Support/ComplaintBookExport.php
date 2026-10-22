<?php

namespace App\Support;

use App\Models\Complaint;
use Carbon\CarbonInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The sheets filed in a period as a CSV file that opens in Excel, for an INDECOPI inspection
 * or the store's own records.
 */
final class ComplaintBookExport
{
    /**
     * @var list<string>
     */
    private const array HEADINGS = [
        'N°', 'Fecha', 'Tipo', 'Nombre', 'Documento', 'Domicilio', 'Teléfono', 'Correo',
        'Menor de edad', 'Apoderado', 'Bien', 'Descripción', 'Monto reclamado', 'Detalle', 'Pedido',
        'Estado', 'Responder hasta', 'Fecha de respuesta', 'Respuesta',
    ];

    public static function download(CarbonInterface $from, CarbonInterface $until): StreamedResponse
    {
        return response()->streamDownload(
            function () use ($from, $until): void {
                echo self::csv($from, $until);
            },
            "libro-de-reclamaciones-{$from->format('Y-m-d')}-al-{$until->format('Y-m-d')}.csv",
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * Starts with a byte-order mark so Excel reads the accents and the "ñ" correctly.
     */
    public static function csv(CarbonInterface $from, CarbonInterface $until): string
    {
        $file = fopen('php://temp', 'r+');
        fwrite($file, "\u{FEFF}");
        fputcsv($file, self::HEADINGS, escape: '');

        Complaint::query()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $until->copy()->endOfDay()])
            ->orderBy('year')
            ->orderBy('sequence')
            ->each(function (Complaint $complaint) use ($file): void {
                fputcsv($file, self::row($complaint), escape: '');
            });

        rewind($file);
        $csv = stream_get_contents($file);
        fclose($file);

        return $csv;
    }

    /**
     * @return list<string>
     */
    private static function row(Complaint $complaint): array
    {
        return [
            $complaint->code(),
            $complaint->created_at->format('d/m/Y H:i'),
            $complaint->type->getLabel(),
            $complaint->consumer_name,
            "{$complaint->document_type->getLabel()} {$complaint->document_number}",
            (string) $complaint->address,
            (string) $complaint->phone,
            $complaint->email,
            $complaint->is_minor ? 'Sí' : 'No',
            (string) $complaint->guardian_name,
            $complaint->item_type->getLabel(),
            $complaint->item_description,
            $complaint->amount ?? '',
            $complaint->detail,
            $complaint->consumer_request,
            match (true) {
                $complaint->isAnswered() => 'Respondida',
                $complaint->responseDueDate()->isPast() => 'Vencida',
                default => 'Por responder',
            },
            $complaint->responseDueDate()->format('d/m/Y'),
            $complaint->responded_at?->format('d/m/Y') ?? '',
            (string) $complaint->response,
        ];
    }
}
