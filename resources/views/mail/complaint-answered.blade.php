<x-mail::message>
# Respuesta a tu hoja de reclamación N° {{ $complaint->code() }}

Hola {{ $complaint->consumer_name }}, esta es nuestra respuesta a tu {{ strtolower($complaint->type->getLabel()) }} del {{ $complaint->created_at->format('d/m/Y') }} sobre "{{ $complaint->item_description }}".

{{ $complaint->response }}

Si tienes dudas, responde a este correo@if ($settings->whatsapp_number) o escríbenos por WhatsApp al {{ $settings->whatsapp_number }}@endif.

Gracias,<br>
{{ $settings->store_name }}

<x-mail::subcopy>
{{ $settings->legal_name ?: $settings->store_name }}@if ($settings->ruc) · RUC {{ $settings->ruc }}@endif. Respuesta enviada el {{ $complaint->responded_at->format('d/m/Y') }}.
</x-mail::subcopy>
</x-mail::message>
