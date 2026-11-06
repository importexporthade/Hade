<?php

namespace Database\Seeders;

use App\Enums\PageVisibility;
use App\Models\Page;
use App\Models\StoreSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Starter texts for the pages every store in Peru should publish: privacy (Ley N° 29733),
 * terms of use and sale, a template for exchanges and returns, and frequent questions.
 * They are a starting point the owner reviews and edits in the manager, so pages that
 * already exist are never overwritten. In production run `php artisan db:seed --class=InformationPagesSeeder --force`.
 */
class InformationPagesSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $contact = $this->contactLine(StoreSetting::current());
        $sortOrder = (int) Page::query()->max('sort_order');

        $pages = [
            'preguntas-frecuentes' => ['Preguntas frecuentes', $this->frequentQuestions($contact), PageVisibility::MenuAndFooter],
            'cambios-y-devoluciones' => ['Cambios y devoluciones', $this->exchangesAndReturns($contact), PageVisibility::Hidden],
            'terminos-y-condiciones' => ['Términos y condiciones', $this->termsAndConditions($contact), PageVisibility::MenuAndFooter],
            'politica-de-privacidad' => ['Política de privacidad', $this->privacyPolicy($contact), PageVisibility::MenuAndFooter],
        ];

        foreach ($pages as $slug => [$title, $content, $visibility]) {
            Page::query()->firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'content' => $content,
                'visibility' => $visibility,
                'sort_order' => ++$sortOrder,
            ]);
        }
    }

    /**
     * How customers reach the store, from the contact details saved in the settings.
     */
    private function contactLine(StoreSetting $settings): string
    {
        $channels = array_filter([
            $settings->whatsapp_number ? 'por WhatsApp al <strong>+'.e($settings->whatsapp_number).'</strong>' : null,
            $settings->email ? 'por correo a <a href="mailto:'.e($settings->email).'">'.e($settings->email).'</a>' : null,
        ]);

        return $channels === [] ? 'por los medios de contacto que figuran al pie de la página' : implode(' o ', $channels);
    }

    private function frequentQuestions(string $contact): string
    {
        return <<<HTML
            <h2>¿Cómo hago un pedido?</h2>
            <p>Elige tus productos, agrégalos al carrito y envía tu pedido por WhatsApp. Se abrirá un chat con tu pedido ya escrito; te respondemos para confirmar el stock, el total, la dirección de entrega y el pago.</p>

            <h2>¿Cómo pago?</h2>
            <p>Al confirmar tu pedido por WhatsApp te indicamos los medios de pago disponibles. Preparamos el envío cuando el pago está confirmado.</p>

            <h2>¿Hacen envíos a todo el Perú?</h2>
            <p>Sí, enviamos a todo el país. Al confirmar tu pedido te indicamos el plazo estimado de entrega según tu ciudad.</p>

            <h2>¿Cómo se aplican los stickers de uñas?</h2>
            <ol>
                <li>Limpia y seca bien tus uñas, sin restos de esmalte ni aceite.</li>
                <li>Elige el sticker del tamaño de cada uña, retíralo y pégalo presionando desde el centro hacia los bordes.</li>
                <li>Lima el sobrante hacia abajo y sella con la lámpara durante el tiempo que indica el empaque.</li>
            </ol>

            <h2>¿Cuánto duran?</h2>
            <p>Con una buena aplicación duran entre 10 y 14 días. Evita el agua caliente y los productos de limpieza sin guantes durante las primeras horas.</p>

            <h2>¿Cómo los retiro sin dañar mis uñas?</h2>
            <p>Levanta el borde con un palito de naranjo y retíralo con suavidad. Si cuesta, aplica un poco de aceite de cutícula en el borde y espera unos minutos.</p>

            <h2>¿Necesito una lámpara?</h2>
            <p>Sí, los stickers de gel se sellan con una lámpara UV/LED. Si no tienes una, puedes llevar uno de nuestros kits, que ya la incluyen.</p>

            <h2>¿Puedo cambiar un producto?</h2>
            <p>Escríbenos {$contact} y te contamos las condiciones para tu caso. También puedes revisar nuestra política de Cambios y devoluciones en la sección Legal, al pie de esta página.</p>

            <h2>¿Tienes otra pregunta?</h2>
            <p>Escríbenos {$contact}.</p>
            HTML;
    }

    /**
     * Only a template: the owner sets the real deadlines and conditions, so it is created
     * hidden and the footer keeps showing "próximamente" until she publishes it.
     */
    private function exchangesAndReturns(string $contact): string
    {
        return <<<HTML
            <p><strong>Plantilla para completar:</strong> reemplaza los textos entre [corchetes] con las condiciones de la tienda, borra este párrafo y cambia la página a visible. Mientras esté oculta, nadie la ve.</p>

            <p>Queremos que estés feliz con tu compra. Si algo no salió bien, aquí te contamos cómo lo resolvemos.</p>

            <h2>1. Productos con fallas o pedidos equivocados</h2>
            <p>Si tu producto llegó dañado, con una falla de fábrica o no es el que pediste, avísanos dentro de los <strong>[7] días calendario</strong> siguientes a la entrega {$contact}, con tu número de pedido y fotos o un video del producto y su empaque.</p>
            <p>Revisamos tu caso en un plazo de <strong>[3] días hábiles</strong> y, según corresponda, te enviamos el producto correcto o uno nuevo sin costo, o te devolvemos el dinero.</p>

            <h2>2. Cambios por otro diseño</h2>
            <p>Puedes cambiar un producto por otro diseño dentro de los <strong>[7] días calendario</strong> siguientes a la entrega, siempre que esté <strong>sin abrir, sin usar y en su empaque original sellado</strong>. El costo del envío del cambio corre por <strong>[tu cuenta / nuestra cuenta]</strong>.</p>

            <h2>3. Productos que no aceptan cambio</h2>
            <p>Por higiene, no aceptamos cambios de stickers, guantes ni productos de cuidado que hayan sido abiertos o usados, salvo que tengan una falla de fábrica. [Agrega aquí otros productos sin cambio, por ejemplo los comprados en liquidación.]</p>

            <h2>4. Lámparas y accesorios eléctricos</h2>
            <p>Las lámparas tienen una garantía de <strong>[X meses]</strong> contra fallas de fábrica. Si la tuya deja de funcionar, escríbenos con un video que muestre el problema. La garantía no cubre golpes, caídas, humedad ni el uso con un cargador distinto al incluido.</p>

            <h2>5. Devolución del dinero</h2>
            <p>Cuando corresponde una devolución, te reembolsamos por <strong>[el mismo medio con el que pagaste]</strong> dentro de los <strong>[X días hábiles]</strong> siguientes a la aprobación. [Indica si el costo del envío original también se devuelve.]</p>

            <h2>6. Cómo enviarnos el producto</h2>
            <p>[Explica cómo se entrega el producto: dirección o agencia a la que se envía, si recogen en Lima, y qué debe incluir el paquete.]</p>

            <h2>7. Tus derechos</h2>
            <p>Esta política no limita los derechos que te reconoce el Código de Protección y Defensa del Consumidor (Ley N° 29571), incluida la garantía legal de los productos. Si no estás conforme con la solución, puedes dejar tu reclamo en nuestro <a href="/information/complaints">Libro de Reclamaciones</a>.</p>
            HTML;
    }

    private function termsAndConditions(string $contact): string
    {
        return <<<HTML
            <p><em>Última actualización: 2 de noviembre de 2026.</em></p>
            <p>Estos términos regulan el uso de esta página web y las compras que hagas a través de ella. Al navegar por la tienda o hacer un pedido aceptas estos términos, así que te pedimos leerlos con calma. La razón social, el RUC y el domicilio de la empresa que vende figuran al pie de esta página.</p>

            <h2>1. Quiénes somos</h2>
            <p>Hade es una tienda peruana de stickers de uñas para manos y pies, y de los accesorios para aplicarlos, como lámparas, limas y productos de cuidado. Vendemos y enviamos únicamente dentro del Perú.</p>

            <h2>2. Uso de la página web</h2>
            <p>Puedes usar esta web para conocer nuestros productos, armar tu pedido y contactarnos. Al usarla te comprometes a:</p>
            <ul>
                <li>Dar información verdadera cuando hagas un pedido o un reclamo.</li>
                <li>No usarla con fines ilegales, para suplantar a otra persona ni para enviar pedidos o reclamos falsos.</li>
                <li>No intentar acceder a zonas restringidas, alterar su funcionamiento, introducir programas dañinos ni copiar su contenido de forma automática.</li>
            </ul>
            <p>Podemos actualizar, cambiar o suspender temporalmente la web, por ejemplo por mantenimiento. Hacemos lo posible para que funcione siempre, pero no podemos garantizar que esté libre de interrupciones o errores.</p>

            <h2>3. Productos, fotos y precios</h2>
            <p>Los precios están en soles (S/) y son precios finales, con los impuestos incluidos. El costo del envío no está incluido en el precio de los productos: te lo informamos antes de que pagues.</p>
            <p>Las fotos son referenciales. El color de los diseños puede variar ligeramente según la pantalla, y el tamaño de los stickers puede no coincidir exactamente con tus uñas. Los precios y promociones pueden cambiar, pero respetamos el precio que te confirmamos en tu pedido.</p>
            <p>Si por un error evidente un producto aparece con un precio equivocado, te lo avisaremos antes de cobrarte y podrás elegir si sigues con la compra o la cancelas.</p>

            <h2>4. Cómo se hace un pedido</h2>
            <ol>
                <li>Agregas los productos al carrito y completas tus datos de envío.</li>
                <li>Al tocar el botón de WhatsApp, se abre un chat con tu pedido ya escrito. Enviarlo es una <strong>solicitud de compra</strong>, todavía no una compra cerrada.</li>
                <li>Te respondemos confirmando el stock, el costo del envío y el total.</li>
                <li>El pedido queda confirmado cuando recibimos tu pago. Si un producto se agotó, te lo avisamos antes de cobrarte.</li>
            </ol>
            <p>Para comprar debes ser mayor de edad o contar con la autorización de tus padres o tutores.</p>

            <h2>5. Pago y comprobante</h2>
            <p>Te indicamos los medios de pago disponibles al confirmar tu pedido. No pedimos ni guardamos datos de tarjetas en esta web. Te entregamos el comprobante de pago que corresponda (boleta o factura); si necesitas factura, pídela al confirmar tu pedido e indícanos tu RUC.</p>

            <h2>6. Envíos y entrega</h2>
            <p>Hacemos envíos a Lima y a provincias. El plazo de entrega estimado depende de tu ciudad y te lo informamos al confirmar tu pedido; los envíos a provincia pueden hacerse por agencia. Revisa que tu nombre, dirección y teléfono sean correctos: si un envío no se puede entregar por datos equivocados, coordinaremos contigo un nuevo envío, cuyo costo puede correr por tu cuenta.</p>
            <p>Al recibir tu pedido, revisa que el paquete esté en buen estado. Si notas algún daño, tómale fotos y escríbenos lo antes posible.</p>

            <h2>7. Uso seguro de los productos</h2>
            <ul>
                <li>Sigue las indicaciones del empaque y de nuestras <a href="/information/preguntas-frecuentes">Preguntas frecuentes</a> para aplicar y retirar los stickers.</li>
                <li>Si tienes piel o uñas sensibles, prueba primero en una sola uña. Si notas irritación, enrojecimiento o picazón, retira el producto y consulta a un especialista.</li>
                <li>No apliques los productos sobre uñas o piel lastimadas, con hongos o infecciones.</li>
                <li>Usa la lámpara UV/LED siguiendo su manual, sin mirar directamente su luz y solo el tiempo indicado.</li>
                <li>Guarda los productos lejos del alcance de los niños, del calor y de la luz directa del sol.</li>
            </ul>

            <h2>8. Promociones</h2>
            <p>Cada promoción indica sus condiciones y su vigencia. Salvo que se diga lo contrario, las promociones no son acumulables y aplican hasta agotar stock.</p>

            <h2>9. Cambios, devoluciones y reclamos</h2>
            <p>Los cambios y devoluciones se rigen por nuestra política de Cambios y devoluciones, que encontrarás en la sección Legal al pie de esta página. Además, ponemos a tu disposición el <a href="/information/complaints">Libro de Reclamaciones</a> virtual, conforme al Código de Protección y Defensa del Consumidor (Ley N° 29571).</p>

            <h2>10. Propiedad intelectual</h2>
            <p>La marca Hade, los diseños, las fotos, los textos y el resto del contenido de esta web nos pertenecen o los usamos con autorización. No puedes copiarlos, venderlos ni usarlos con fines comerciales sin nuestro permiso por escrito.</p>

            <h2>11. Enlaces a otros servicios</h2>
            <p>La web tiene enlaces a WhatsApp y a nuestras redes sociales. Esos servicios pertenecen a otras empresas y tienen sus propios términos y políticas de privacidad, de los que no somos responsables.</p>

            <h2>12. Tus derechos como consumidor</h2>
            <p>Nada de lo que dicen estos términos limita los derechos que te reconoce el Código de Protección y Defensa del Consumidor ni las demás normas peruanas.</p>

            <h2>13. Datos personales</h2>
            <p>Tratamos tus datos según nuestra <a href="/information/politica-de-privacidad">Política de privacidad</a>.</p>

            <h2>14. Cambios a estos términos</h2>
            <p>Podemos actualizar estos términos. La nueva versión se publica en esta página con su fecha de actualización, y se aplica a los pedidos hechos desde ese momento.</p>

            <h2>15. Ley aplicable</h2>
            <p>Estos términos se rigen por las leyes de la República del Perú. Ante cualquier diferencia, buscaremos primero resolverla contigo directamente, sin perjuicio de tu derecho de acudir al INDECOPI o a las autoridades competentes.</p>

            <h2>16. Contacto</h2>
            <p>Si tienes dudas sobre estos términos, escríbenos {$contact}.</p>
            HTML;
    }

    private function privacyPolicy(string $contact): string
    {
        return <<<HTML
            <p><em>Última actualización: 2 de noviembre de 2026.</em></p>
            <p>Cuidamos tus datos personales conforme a la Ley N° 29733, Ley de Protección de Datos Personales, y su reglamento, aprobado por el Decreto Supremo N° 016-2024-JUS. Aquí te explicamos qué datos usamos, para qué y cómo puedes ejercer tus derechos.</p>

            <h2>1. Quién es responsable de tus datos</h2>
            <p>El responsable es la empresa titular de esta tienda, cuya razón social, RUC y domicilio figuran al pie de esta página. Puedes escribirnos sobre tus datos {$contact}.</p>

            <h2>2. Qué datos recogemos</h2>
            <ul>
                <li><strong>Al hacer un pedido:</strong> tu nombre y apellido, si el envío es a Lima o a provincia, tu distrito o ciudad y la nota que quieras dejar. Al enviarnos el pedido por WhatsApp también recibimos tu número de teléfono y tu nombre de perfil, y durante la conversación podemos pedirte la dirección de entrega, tu documento de identidad (si la agencia de envíos lo requiere) y el comprobante de tu pago.</li>
                <li><strong>Al usar el Libro de Reclamaciones:</strong> los datos que completas en la hoja (nombre, tipo y número de documento, correo y, si los das, teléfono y dirección; y, si eres menor de edad, el nombre de tu padre, madre o apoderado) y el detalle de tu reclamo o queja.</li>
                <li><strong>Al navegar por la tienda:</strong> los productos que ves, agregas o quitas del carrito, asociados a un identificador aleatorio que no incluye tu nombre ni tus datos de contacto. Como cualquier web, también registramos datos técnicos como tu dirección IP y el tipo de navegador, para mantener la tienda segura y funcionando.</li>
            </ul>
            <p>No pedimos ni guardamos datos de tarjetas de crédito o débito.</p>

            <h2>3. Para qué usamos tus datos</h2>
            <ul>
                <li>Confirmar, preparar, entregar y dar seguimiento a tus pedidos, y emitir tu comprobante de pago.</li>
                <li>Responder tus consultas, reclamos y quejas en el plazo que manda la ley.</li>
                <li>Saber qué productos interesan más para mejorar la tienda y nuestro catálogo.</li>
                <li>Proteger la web frente a usos indebidos.</li>
                <li>Solo si nos das tu permiso, enviarte novedades y promociones. Puedes pedirnos que dejemos de hacerlo cuando quieras.</li>
            </ul>
            <p>Usamos tus datos porque son necesarios para atender el pedido o el reclamo que tú nos haces, o porque nos diste tu consentimiento. Si no nos das los datos necesarios para el envío, no podremos entregar tu pedido.</p>

            <h2>4. Con quién los compartimos</h2>
            <p>No vendemos ni alquilamos tus datos. Solo los compartimos cuando es necesario para atenderte:</p>
            <ul>
                <li><strong>Empresas de envío y agencias</strong> que llevan tu pedido, con los datos que necesitan para entregarlo.</li>
                <li><strong>Proveedores tecnológicos</strong> que nos dan el alojamiento de la web, el correo y la mensajería, como WhatsApp (de Meta Platforms).</li>
                <li><strong>Autoridades</strong>, como el INDECOPI o la SUNAT, cuando la ley lo exige.</li>
            </ul>

            <h2>5. Datos que salen del Perú</h2>
            <p>Algunos de estos proveedores, como WhatsApp o los servicios de alojamiento y correo, guardan la información en servidores ubicados fuera del Perú. Al usar estos canales, tus datos pueden viajar a otros países; solo trabajamos con proveedores que ofrecen niveles adecuados de protección. Lo que compartes por WhatsApp también se rige por la política de privacidad de WhatsApp.</p>

            <h2>6. Cuánto tiempo los guardamos</h2>
            <p>Guardamos los datos de tus pedidos mientras los necesitemos para atenderte y para cumplir nuestras obligaciones contables y tributarias. Las hojas del Libro de Reclamaciones se conservan al menos dos años desde su registro, como exige la ley. Los datos de navegación los usamos de forma agregada y los borramos cuando dejan de ser útiles.</p>

            <h2>7. Cookies y datos guardados en tu dispositivo</h2>
            <p>Esta web usa pocas cookies y todas son nuestras: no usamos cookies de publicidad ni de redes sociales.</p>
            <ul>
                <li><strong>hade-session y XSRF-TOKEN:</strong> son necesarias para que la web funcione y para protegerla de ataques. Se borran al poco tiempo de cerrar el navegador o al terminar tu visita.</li>
                <li><strong>hade_visitor:</strong> guarda un identificador aleatorio durante un año, para contar las visitas y saber qué productos interesan sin saber quién eres.</li>
            </ul>
            <p>Además, tu carrito y los datos de envío que escribes se guardan <strong>solo en tu navegador</strong>, para que no se pierdan si cierras la página y no tengas que escribirlos de nuevo en tu próxima compra. No nos llegan hasta que tú envías el pedido por WhatsApp.</p>
            <p>Puedes borrar las cookies y estos datos cuando quieras desde la configuración de tu navegador. Si lo haces, se vaciará tu carrito.</p>

            <h2>8. Menores de edad</h2>
            <p>Esta tienda no está dirigida a menores de 14 años. Si eres menor de 14 años, necesitas el permiso de tus padres o tutores para darnos tus datos. Si eres madre, padre o tutor y crees que tu hija o hijo nos dio sus datos sin tu permiso, escríbenos para borrarlos.</p>

            <h2>9. Tus derechos</h2>
            <p>Puedes pedir en cualquier momento, sin costo, <strong>acceder</strong> a tus datos, <strong>rectificarlos</strong>, <strong>cancelarlos</strong> u <strong>oponerte</strong> a su uso (derechos ARCO), así como retirar el consentimiento que nos diste. Escríbenos {$contact} indicando tu nombre, una copia de tu documento de identidad y lo que necesitas, y te responderemos en los plazos que fija la ley.</p>
            <p>Si no estás conforme con nuestra respuesta, puedes acudir a la Autoridad Nacional de Protección de Datos Personales del Ministerio de Justicia y Derechos Humanos.</p>

            <h2>10. Seguridad</h2>
            <p>Protegemos tus datos con medidas técnicas y organizativas razonables, como conexiones cifradas y acceso restringido: solo las personas que los necesitan para atenderte pueden verlos.</p>

            <h2>11. Cambios a esta política</h2>
            <p>Si cambiamos esta política, publicaremos la nueva versión en esta página con su fecha de actualización.</p>
            HTML;
    }
}
