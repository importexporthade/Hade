@php
    use App\Enums\ComplaintItemType;
    use App\Enums\ComplaintType;
    use App\Enums\IdentityDocument;
    use App\Models\Complaint;
    use App\Support\Price;

    $provider = $settings->legal_name ?: $settings->store_name;
@endphp

<x-store-layout title="Libro de Reclamaciones" description="Libro de Reclamaciones virtual de {{ $settings->store_name }}, conforme al Código de Protección y Defensa del Consumidor.">
    <div class="store-container max-w-3xl pt-10">
        <div class="flex items-center gap-3">
            <x-store.icon name="book" class="size-9 text-emerald" />
            <h1 class="font-serif text-3xl">Libro de Reclamaciones</h1>
        </div>
        <p class="mt-2 text-sm text-ink/70">Conforme al Código de Protección y Defensa del Consumidor.</p>

        @if ($filed)
            <section class="mt-8 border border-emerald/30 bg-white" role="status" aria-labelledby="receipt-title">
                <div class="flex gap-3 border-b border-emerald/20 bg-sage-light p-5">
                    <x-store.icon name="check" class="size-6 shrink-0 text-emerald" />
                    <div>
                        <h2 id="receipt-title" class="font-semibold text-emerald">Registramos tu hoja de reclamación N° {{ $filed->code() }}</h2>
                        <p class="mt-1 text-sm text-ink/80">Esta es tu constancia. También enviamos una copia a {{ $filed->email }}.</p>
                    </div>
                </div>

                <dl class="grid gap-x-6 gap-y-3 p-5 text-sm sm:grid-cols-[11rem_1fr]">
                    <dt class="text-ink/60">Fecha y hora</dt>
                    <dd>{{ $filed->created_at->format('d/m/Y \a \l\a\s h:i a') }}</dd>
                    <dt class="text-ink/60">Proveedor</dt>
                    <dd>{{ $provider }}@if ($settings->ruc) · RUC {{ $settings->ruc }}@endif</dd>
                    <dt class="text-ink/60">Consumidor</dt>
                    <dd>{{ $filed->consumer_name }} · {{ $filed->document_type->getLabel() }} {{ $filed->document_number }}</dd>
                    <dt class="text-ink/60">Correo</dt>
                    <dd>{{ $filed->email }}</dd>
                    <dt class="text-ink/60">Tipo</dt>
                    <dd>{{ $filed->type->getLabel() }}</dd>
                    <dt class="text-ink/60">Producto o servicio</dt>
                    <dd>{{ $filed->item_type->getLabel() }}: {{ $filed->item_description }}</dd>
                    <dt class="text-ink/60">Monto reclamado</dt>
                    <dd>{{ $filed->amount !== null ? Price::format($filed->amount) : 'No indicado' }}</dd>
                    <dt class="text-ink/60">Detalle</dt>
                    <dd class="whitespace-pre-line">{{ $filed->detail }}</dd>
                    <dt class="text-ink/60">Pedido</dt>
                    <dd class="whitespace-pre-line">{{ $filed->consumer_request }}</dd>
                    <dt class="text-ink/60">Respuesta</dt>
                    <dd>Te responderemos por correo hasta el {{ $filed->responseDueDate()->format('d/m/Y') }}.</dd>
                </dl>

                <div class="flex flex-wrap gap-3 border-t border-ink/10 p-5">
                    <a href="{{ $filed->receiptUrl() }}" class="btn-sage">Descargar constancia (PDF)</a>
                    <a href="{{ route('complaints.create') }}" class="btn-outline">Registrar otra hoja</a>
                </div>
            </section>
        @else
            <form method="post" action="{{ route('complaints.store') }}" class="mt-8 space-y-10" novalidate>
                @csrf

                <fieldset class="space-y-4">
                    <legend class="text-lg font-bold">1. Identificación del proveedor</legend>

                    <dl class="grid gap-x-6 gap-y-2 border border-ink/10 bg-white p-5 text-sm sm:grid-cols-[auto_1fr]">
                        <dt class="text-ink/60">Razón social</dt>
                        <dd class="font-medium">{{ $provider }}</dd>
                        @if ($settings->ruc)
                            <dt class="text-ink/60">RUC</dt>
                            <dd>{{ $settings->ruc }}</dd>
                        @endif
                        @if ($settings->legal_address)
                            <dt class="text-ink/60">Dirección</dt>
                            <dd>{{ $settings->legal_address }}</dd>
                        @endif
                    </dl>
                </fieldset>

                <fieldset class="space-y-5">
                    <legend class="text-lg font-bold">2. Identificación del consumidor</legend>

                    <x-store.field name="consumer_name" label="Nombre completo" autocomplete="name" required />

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="document_type" class="form-label">Tipo de documento</label>
                            <select id="document_type" name="document_type" class="form-input" required @error('document_type') aria-invalid="true" @enderror>
                                @foreach (IdentityDocument::cases() as $document)
                                    <option value="{{ $document->value }}" @selected(old('document_type', IdentityDocument::Dni->value) === $document->value)>{{ $document->getLabel() }}</option>
                                @endforeach
                            </select>
                            @error('document_type') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <x-store.field name="document_number" label="Número de documento" inputmode="numeric" required />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-store.field name="email" label="Correo electrónico" type="email" autocomplete="email" placeholder="tu@correo.com" hint="Aquí te llegará la constancia y nuestra respuesta." required />
                        <x-store.field name="phone" label="Teléfono" type="tel" autocomplete="tel" inputmode="tel" hint="Opcional." />
                    </div>

                    <x-store.field name="address" label="Domicilio" autocomplete="street-address" hint="Opcional." />

                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_minor" value="0">
                            <input type="checkbox" name="is_minor" value="1" class="size-4 accent-emerald" data-toggles="guardian" @checked(old('is_minor'))>
                            Soy menor de edad
                        </label>
                        <div class="mt-4" data-toggled="guardian" @unless (old('is_minor')) hidden @endunless>
                            <x-store.field name="guardian_name" label="Nombre del padre, madre o apoderado" />
                        </div>
                    </div>
                </fieldset>

                <fieldset class="space-y-5">
                    <legend class="text-lg font-bold">3. Información del reclamo o queja</legend>

                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach (ComplaintType::cases() as $complaintType)
                            <label class="flex cursor-pointer gap-3 border border-ink/15 bg-white p-4 has-checked:border-emerald has-checked:bg-sage-light">
                                <input type="radio" name="type" value="{{ $complaintType->value }}" class="mt-0.5 size-4 accent-emerald" @checked(old('type', ComplaintType::Claim->value) === $complaintType->value)>
                                <span class="text-sm">
                                    <span class="block font-semibold">{{ $complaintType->getLabel() }}</span>
                                    <span class="mt-0.5 block text-ink/70">{{ $complaintType->getDescription() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('type') <p class="form-error">{{ $message }}</p> @enderror

                    <div>
                        <label for="filed_on" class="form-label">Fecha de presentación</label>
                        <input id="filed_on" type="text" value="{{ now()->format('d/m/Y') }}" class="form-input bg-ink/5 sm:w-48" readonly>
                    </div>

                    <div>
                        <p class="form-label">¿Es un producto o un servicio?</p>
                        <div class="flex gap-6">
                            @foreach (ComplaintItemType::cases() as $itemType)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="radio" name="item_type" value="{{ $itemType->value }}" class="size-4 accent-emerald" @checked(old('item_type', ComplaintItemType::Product->value) === $itemType->value)>
                                    {{ $itemType->getLabel() }}
                                </label>
                            @endforeach
                        </div>
                        @error('item_type') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-[1fr_12rem]">
                        <x-store.field name="item_description" label="Producto o servicio" placeholder="Producto, pedido o servicio" required />
                        <x-store.field name="amount" label="Monto reclamado (S/)" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00" hint="Opcional." />
                    </div>

                    <x-store.field name="detail" label="Descripción de los hechos" type="textarea" maxlength="3000" placeholder="Explica qué ocurrió, incluyendo fechas o número de pedido si corresponde." required />
                    <x-store.field name="consumer_request" label="Pedido concreto del consumidor" type="textarea" rows="3" maxlength="2000" placeholder="Indica qué solución solicitas." required />
                </fieldset>

                <div class="space-y-4 border-t border-ink/10 pt-6">
                    <div>
                        <label class="flex items-start gap-3 text-sm">
                            <input type="checkbox" name="privacy" value="1" class="mt-0.5 size-4 shrink-0 accent-emerald" @checked(old('privacy')) @error('privacy') aria-invalid="true" @enderror>
                            <span>Declaro que los datos ingresados son correctos y he leído la información sobre el tratamiento de mis datos personales.</span>
                        </label>
                        @error('privacy') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <details class="border border-ink/10 bg-white p-4 text-xs leading-relaxed text-ink/70">
                        <summary class="cursor-pointer font-semibold text-ink">Tratamiento de tus datos personales</summary>
                        <div class="mt-3 space-y-2">
                            <p>{{ $provider }}@if ($settings->ruc) (RUC {{ $settings->ruc }})@endif es responsable de los datos que ingresas en esta hoja, conforme a la Ley N° 29733, Ley de Protección de Datos Personales.</p>
                            <p>Los usamos solo para registrar y responder tu reclamo o queja, y para cumplir lo que exige la norma del Libro de Reclamaciones. Los conservamos al menos dos años, como pide esa norma, y solo los compartimos con el INDECOPI u otra autoridad si lo solicita.</p>
                            <p>Puedes pedir acceder a tus datos, corregirlos o cancelarlos{{ $settings->email ? " escribiendo a {$settings->email}" : '' }}.</p>
                        </div>
                    </details>

                    <div class="space-y-2 text-xs leading-relaxed text-ink/60">
                        <p>La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.</p>
                        <p>El proveedor deberá dar respuesta al reclamo o queja en un plazo no mayor a {{ Complaint::RESPONSE_DAYS }} días calendario.</p>
                    </div>
                </div>

                <button type="submit" class="btn-sage w-full sm:w-auto">Enviar hoja de reclamación</button>
            </form>
        @endif
    </div>
</x-store-layout>
