<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Hoja de reclamación N° {{ $complaint->code() }}</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1f2a26; line-height: 1.45; }
        h1 { font-size: 15px; margin: 0; letter-spacing: 0.5px; }
        .header { border-bottom: 2px solid #1f5c45; padding-bottom: 8px; margin-bottom: 12px; }
        .header td { vertical-align: top; }
        .number { text-align: right; }
        .number strong { display: block; font-size: 14px; color: #1f5c45; }
        .muted { color: #5b5f59; }
        table { width: 100%; border-collapse: collapse; }
        .section { margin-bottom: 12px; }
        .section-title { background: #1f5c45; color: #fff; font-weight: bold; padding: 4px 8px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px; }
        .fields td { border: 1px solid #cdc6ba; padding: 5px 8px; vertical-align: top; }
        .fields .label { width: 28%; background: #f0ece5; font-weight: bold; }
        .box { border: 1px solid #cdc6ba; border-top: 0; padding: 8px; white-space: pre-line; }
        .check { display: inline-block; width: 9px; height: 9px; border: 1px solid #1f2a26; margin-right: 4px; text-align: center; line-height: 9px; font-size: 9px; }
        .types td { border: 1px solid #cdc6ba; padding: 6px 8px; width: 50%; vertical-align: top; }
        .notes { margin-top: 14px; font-size: 8.5px; color: #5b5f59; border-top: 1px solid #cdc6ba; padding-top: 6px; }
        .notes p { margin: 0 0 3px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <h1>LIBRO DE RECLAMACIONES</h1>
                <div><strong>{{ $settings->legal_name ?: $settings->store_name }}</strong>@if ($settings->ruc) · RUC {{ $settings->ruc }}@endif</div>
                @if ($settings->legal_address)
                    <div class="muted">{{ $settings->legal_address }}</div>
                @endif
            </td>
            <td class="number">
                HOJA DE RECLAMACIÓN
                <strong>N° {{ $complaint->code() }}</strong>
                <span class="muted">Registrada el {{ $complaint->created_at->format('d/m/Y \a \l\a\s h:i a') }}</span>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">1. Identificación del consumidor reclamante</div>
        <table class="fields">
            <tr><td class="label">Nombre</td><td>{{ $complaint->consumer_name }}</td></tr>
            <tr><td class="label">Documento</td><td>{{ $complaint->document_type->getLabel() }} {{ $complaint->document_number }}</td></tr>
            <tr><td class="label">Domicilio</td><td>{{ $complaint->address ?: 'No indicado' }}</td></tr>
            <tr><td class="label">Teléfono</td><td>{{ $complaint->phone ?: 'No indicado' }}</td></tr>
            <tr><td class="label">Correo electrónico</td><td>{{ $complaint->email }}</td></tr>
            @if ($complaint->is_minor)
                <tr><td class="label">Padre, madre o apoderado</td><td>{{ $complaint->guardian_name }} (la reclamante es menor de edad)</td></tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. Identificación del bien contratado</div>
        <table class="fields">
            <tr><td class="label">Tipo</td><td>{{ $complaint->item_type->getLabel() }}</td></tr>
            <tr><td class="label">Monto reclamado</td><td>{{ $complaint->amount !== null ? \App\Support\Price::format($complaint->amount) : 'No indicado' }}</td></tr>
            <tr><td class="label">Descripción</td><td>{{ $complaint->item_description }}</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">3. Detalle de la reclamación y pedido del consumidor</div>
        <table class="types">
            <tr>
                @foreach (\App\Enums\ComplaintType::cases() as $type)
                    <td>
                        <span class="check">{{ $complaint->type === $type ? 'X' : '' }}</span>
                        <strong>{{ $type->getLabel() }}</strong><br>
                        <span class="muted">{{ $type->getDescription() }}</span>
                    </td>
                @endforeach
            </tr>
        </table>
        <div class="box"><strong>Detalle:</strong> {{ $complaint->detail }}</div>
        <div class="box"><strong>Pedido:</strong> {{ $complaint->consumer_request }}</div>
    </div>

    <div class="section">
        <div class="section-title">4. Observaciones y acciones adoptadas por el proveedor</div>
        @if ($complaint->isAnswered())
            <div class="box"><strong>Respondida el {{ $complaint->responded_at->format('d/m/Y') }}:</strong> {{ $complaint->response }}</div>
        @else
            <div class="box muted">Pendiente de respuesta. Plazo hasta el {{ $complaint->responseDueDate()->format('d/m/Y') }}.</div>
        @endif
    </div>

    <div class="notes">
        <p>* La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.</p>
        <p>* El proveedor debe dar respuesta al reclamo o queja en un plazo no mayor a {{ \App\Models\Complaint::RESPONSE_DAYS }} días calendario.</p>
        <p>Documento generado el {{ now()->format('d/m/Y \a \l\a\s h:i a') }}.</p>
    </div>
</body>
</html>
