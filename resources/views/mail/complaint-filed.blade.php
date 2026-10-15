<x-mail::message>
# Hoja de reclamación N° {{ $complaint->code() }}

Hola {{ $complaint->consumer_name }}, registramos tu {{ strtolower($complaint->type->getLabel()) }} el {{ $complaint->created_at->format('d/m/Y \a \l\a\s H:i') }}. Esta es tu copia.

Te responderemos a este correo en un plazo máximo de {{ \App\Models\Complaint::RESPONSE_BUSINESS_DAYS }} días hábiles, hasta el {{ $complaint->responseDueDate()->format('d/m/Y') }}.

## Proveedor

{{ $settings->legal_name ?: $settings->store_name }}@if ($settings->ruc) · RUC {{ $settings->ruc }}@endif

@if ($settings->legal_address)
{{ $settings->legal_address }}
@endif

## Tus datos

- **Nombre:** {{ $complaint->consumer_name }}
- **Documento:** {{ $complaint->document_type->getLabel() }} {{ $complaint->document_number }}
- **Domicilio:** {{ $complaint->address }}
- **Teléfono:** {{ $complaint->phone }}
- **Correo:** {{ $complaint->email }}
@if ($complaint->is_minor)
- **Menor de edad, representado por:** {{ $complaint->guardian_name }}
@endif

## Producto o servicio

- **Tipo:** {{ $complaint->item_type->getLabel() }}
- **Descripción:** {{ $complaint->item_description }}
@if ($complaint->amount !== null)
- **Monto reclamado:** {{ \App\Support\Price::format($complaint->amount) }}
@endif

## Detalle de tu {{ strtolower($complaint->type->getLabel()) }}

{{ $complaint->detail }}

**Tu pedido:** {{ $complaint->consumer_request }}

<x-mail::subcopy>
La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.
</x-mail::subcopy>
</x-mail::message>
