<x-mail::message>
# Confirma que eres tú

Hola {{ $name }}, alguien acaba de entrar con tu contraseña a {{ $settings->store_name }} Manager. Para terminar de entrar, escribe este código:

<x-mail::panel>
<div style="font-size: 28px; font-weight: bold; letter-spacing: 8px; text-align: center;">{{ $code }}</div>
</x-mail::panel>

El código vence en {{ $codeExpiryMinutes }} minutos y solo sirve una vez.

**¿No fuiste tú?** Alguien tiene tu contraseña: cámbiala desde «Perfil» cuanto antes.

Gracias,<br>
{{ $settings->store_name }}

<x-mail::subcopy>
Recibiste este correo porque tu cuenta es de administración IT y siempre pide este código al entrar. Nunca te pediremos este código por teléfono ni por WhatsApp.
</x-mail::subcopy>
</x-mail::message>
