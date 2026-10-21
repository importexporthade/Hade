<x-mail::message>
# Desbloquea tu cuenta

Hola {{ $user->name }}, bloqueamos tu cuenta de {{ $settings->store_name }} Manager por seguridad, después de {{ $maxFailedLogins }} intentos de inicio de sesión con una contraseña incorrecta.

Escribe este código en la pantalla «Desbloquea tu cuenta»:

<x-mail::panel>
<div style="font-size: 28px; font-weight: bold; letter-spacing: 8px; text-align: center;">{{ $code }}</div>
</x-mail::panel>

El código vence en {{ $codeMinutes }} minutos y solo sirve una vez.

**¿No fuiste tú?** Alguien intentó entrar con tu correo. Después de desbloquear la cuenta, cambia tu contraseña desde «Perfil».

Gracias,<br>
{{ $settings->store_name }}

<x-mail::subcopy>
Recibiste este correo porque es el de la cuenta del panel manager. Nunca te pediremos este código por teléfono ni por WhatsApp.
</x-mail::subcopy>
</x-mail::message>
