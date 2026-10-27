<?php

namespace Database\Seeders;

use App\Enums\PageVisibility;
use App\Models\Page;
use App\Models\StoreSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Starter texts for the pages every store in Peru should publish: privacy (Ley N° 29733),
 * terms of sale, exchanges and returns, and frequent questions. They are a starting point
 * the owner reviews and edits in the manager, so pages that already exist are never
 * overwritten. In production run `php artisan db:seed --class=InformationPagesSeeder --force`.
 */
class InformationPagesSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $contact = $this->contactLine(StoreSetting::current());
        $sortOrder = (int) Page::query()->max('sort_order');

        $pages = [
            'preguntas-frecuentes' => ['Preguntas frecuentes', $this->frequentQuestions($contact)],
            'cambios-y-devoluciones' => ['Cambios y devoluciones', $this->exchangesAndReturns($contact)],
            'terminos-y-condiciones' => ['Términos y condiciones', $this->termsAndConditions($contact)],
            'politica-de-privacidad' => ['Política de privacidad', $this->privacyPolicy($contact)],
        ];

        foreach ($pages as $slug => [$title, $content]) {
            Page::query()->firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'content' => $content,
                'visibility' => PageVisibility::MenuAndFooter,
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
            <p>Sí, revisa nuestra página de <a href="/information/cambios-y-devoluciones">Cambios y devoluciones</a>.</p>

            <h2>¿Tienes otra pregunta?</h2>
            <p>Escríbenos {$contact}.</p>
            HTML;
    }

    private function exchangesAndReturns(string $contact): string
    {
        return <<<HTML
            <p>Queremos que estés feliz con tu compra. Si algo no salió bien, aquí te contamos cómo lo resolvemos.</p>

            <h2>Productos con fallas o pedidos equivocados</h2>
            <p>Si tu producto llegó dañado, con fallas de fábrica o no es el que pediste, avísanos dentro de los <strong>7 días calendario</strong> siguientes a la entrega {$contact}, con tu número de pedido y fotos o un video del producto y su empaque.</p>
            <p>Revisamos tu caso y, según corresponda, te enviamos el producto correcto o uno nuevo sin costo, o te devolvemos el dinero por el mismo medio con el que pagaste.</p>

            <h2>Cambios por otro diseño</h2>
            <p>Puedes cambiar un producto por otro diseño dentro de los 7 días calendario siguientes a la entrega, siempre que esté <strong>sin abrir, sin usar y en su empaque original sellado</strong>. El costo del envío del cambio corre por tu cuenta.</p>

            <h2>Productos que no aceptan cambio</h2>
            <p>Por higiene, no aceptamos cambios de stickers, guantes ni productos de cuidado que hayan sido abiertos o usados, salvo que tengan una falla de fábrica.</p>

            <h2>Lámparas y accesorios eléctricos</h2>
            <p>Si tu lámpara deja de funcionar por una falla de fábrica, escríbenos con un video que muestre el problema y te ayudamos con el cambio, conforme a la garantía legal que establece el Código de Protección y Defensa del Consumidor.</p>

            <h2>Tus derechos</h2>
            <p>Esta política no limita los derechos que te reconoce el Código de Protección y Defensa del Consumidor (Ley N° 29571). Si no estás conforme con la solución, puedes dejar tu reclamo en nuestro <a href="/information/complaints">Libro de Reclamaciones</a>.</p>
            HTML;
    }

    private function termsAndConditions(string $contact): string
    {
        return <<<HTML
            <p>Estos términos regulan el uso de esta tienda online y las compras que hagas en ella. Al hacer un pedido aceptas estos términos. La razón social y el RUC de la empresa que vende figuran al pie de esta página.</p>

            <h2>1. Productos y precios</h2>
            <p>Los precios se muestran en soles (S/) e incluyen IGV. Las fotos son referenciales: el color puede variar ligeramente según la pantalla. Los precios y promociones pueden cambiar sin previo aviso, pero respetamos el precio vigente al momento en que confirmamos tu pedido.</p>

            <h2>2. Cómo se forma el pedido</h2>
            <p>Agregar productos al carrito y enviarlos por WhatsApp es una solicitud de compra. El pedido queda confirmado cuando te respondemos confirmando el stock y el total, y recibimos tu pago. Si un producto se agotó, te lo avisamos antes de cobrarte.</p>

            <h2>3. Pago</h2>
            <p>Te indicamos los medios de pago disponibles al confirmar tu pedido. No pedimos ni guardamos datos de tarjetas en esta web.</p>

            <h2>4. Envíos</h2>
            <p>Enviamos a todo el Perú. El plazo de entrega estimado depende de tu ciudad y te lo informamos al confirmar el pedido. Verifica que tu dirección y teléfono sean correctos; si el envío no se puede entregar por datos equivocados, coordinaremos contigo un nuevo envío.</p>

            <h2>5. Promociones</h2>
            <p>Cada promoción indica sus condiciones y su vigencia. Salvo que se diga lo contrario, las promociones no son acumulables y aplican hasta agotar stock.</p>

            <h2>6. Cambios, devoluciones y reclamos</h2>
            <p>Los cambios y devoluciones se rigen por nuestra página de <a href="/information/cambios-y-devoluciones">Cambios y devoluciones</a>. Ponemos a tu disposición el <a href="/information/complaints">Libro de Reclamaciones</a> virtual, conforme al Código de Protección y Defensa del Consumidor.</p>

            <h2>7. Datos personales</h2>
            <p>Tratamos tus datos según nuestra <a href="/information/politica-de-privacidad">Política de privacidad</a>.</p>

            <h2>8. Propiedad intelectual</h2>
            <p>Los diseños, fotos, textos y la marca de esta tienda nos pertenecen o los usamos con autorización. No puedes copiarlos ni usarlos con fines comerciales sin nuestro permiso.</p>

            <h2>9. Ley aplicable</h2>
            <p>Estos términos se rigen por las leyes de la República del Perú.</p>

            <h2>10. Contacto</h2>
            <p>Si tienes dudas sobre estos términos, escríbenos {$contact}.</p>
            HTML;
    }

    private function privacyPolicy(string $contact): string
    {
        return <<<HTML
            <p>Cuidamos tus datos personales conforme a la Ley N° 29733, Ley de Protección de Datos Personales, y su reglamento. Aquí te explicamos qué datos usamos y para qué. La razón social y el RUC del responsable de tus datos figuran al pie de esta página.</p>

            <h2>1. Qué datos recogemos</h2>
            <ul>
                <li><strong>Al hacer un pedido por WhatsApp:</strong> tu nombre, teléfono, dirección de entrega y los productos que pides.</li>
                <li><strong>Al usar el Libro de Reclamaciones:</strong> los datos que completas en la hoja (nombre, documento de identidad, correo y, si los das, teléfono y dirección) y el detalle de tu reclamo o queja.</li>
                <li><strong>Al navegar por la tienda:</strong> datos anónimos de uso, como los productos que ves o agregas al carrito, para saber qué productos interesan más. No los asociamos a tu nombre.</li>
            </ul>

            <h2>2. Para qué los usamos</h2>
            <ul>
                <li>Atender, entregar y dar seguimiento a tus pedidos.</li>
                <li>Responder tus consultas, reclamos y quejas en el plazo que manda la ley.</li>
                <li>Mejorar la tienda y nuestro catálogo.</li>
            </ul>
            <p>Solo te enviaremos promociones si nos lo pides o lo aceptas expresamente.</p>

            <h2>3. Con quién los compartimos</h2>
            <p>No vendemos ni alquilamos tus datos. Solo los compartimos con quienes nos ayudan a darte el servicio, como la empresa de envíos que lleva tu pedido, y con las autoridades cuando la ley lo exige (por ejemplo, INDECOPI ante un reclamo).</p>

            <h2>4. Cuánto tiempo los guardamos</h2>
            <p>Guardamos los datos de tus pedidos mientras sean necesarios para atenderte y cumplir nuestras obligaciones contables y tributarias. Las hojas del Libro de Reclamaciones se conservan al menos dos años, como exige la ley.</p>

            <h2>5. Tus derechos</h2>
            <p>Puedes pedir en cualquier momento <strong>acceder</strong> a tus datos, <strong>rectificarlos</strong>, <strong>cancelarlos</strong> u <strong>oponerte</strong> a su uso (derechos ARCO). Escríbenos {$contact} indicando tu nombre y lo que necesitas, y te responderemos en los plazos que fija la ley. Si no estás conforme con nuestra respuesta, puedes acudir a la Autoridad Nacional de Protección de Datos Personales del Ministerio de Justicia y Derechos Humanos.</p>

            <h2>6. Seguridad</h2>
            <p>Protegemos tus datos con medidas técnicas y organizativas razonables, y solo el personal que los necesita para atenderte puede verlos.</p>

            <h2>7. Cambios a esta política</h2>
            <p>Si cambiamos esta política, publicaremos la nueva versión en esta página.</p>
            HTML;
    }
}
