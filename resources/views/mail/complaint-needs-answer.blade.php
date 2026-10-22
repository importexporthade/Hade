<x-mail::message>
@switch($alert)
@case(\App\Enums\ComplaintAlert::Filed)
# Llegó una nueva hoja de reclamación

Una clienta llenó el Libro de Reclamaciones. Tienes hasta el **{{ $deadline }}** para responderle.
@break
@case(\App\Enums\ComplaintAlert::DueSoon)
# Quedan {{ $daysLeft }} días

La hoja N° {{ $complaint->code() }} sigue sin respuesta. El plazo vence el **{{ $deadline }}**.
@break
@case(\App\Enums\ComplaintAlert::DueToday)
# Hoy vence el plazo

La hoja N° {{ $complaint->code() }} debe responderse **hoy, {{ $deadline }}**.
@break
@case(\App\Enums\ComplaintAlert::Overdue)
# Venció el plazo

El plazo para responder la hoja N° {{ $complaint->code() }} venció el **{{ $deadline }}**. Respóndela cuanto antes: responder tarde puede traer una multa de INDECOPI.
@break
@endswitch

- **Hoja:** N° {{ $complaint->code() }} ({{ strtolower($complaint->type->getLabel()) }}), del {{ $complaint->created_at->format('d/m/Y') }}
- **Clienta:** {{ $complaint->consumer_name }}@if ($complaint->phone) · {{ $complaint->phone }}@endif
- **Sobre:** {{ $complaint->item_description }}
- **Qué solicita:** {{ $complaint->consumer_request }}

<x-mail::button :url="$url">
Responder en el panel
</x-mail::button>

<x-mail::subcopy>
El plazo es de {{ \App\Models\Complaint::RESPONSE_DAYS }} días calendario desde que se registró la hoja, contando fines de semana y feriados.
</x-mail::subcopy>
</x-mail::message>
