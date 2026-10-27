@php
    use App\Enums\ComplaintItemType;
    use App\Enums\ComplaintType;
    use App\Enums\IdentityDocument;
    use App\Models\Complaint;
    use App\Support\Price;

    $provider = $settings->legal_name ?: $settings->store_name;
    $sectionCard = 'rounded-[2rem] bg-white/80 p-5 shadow-[0_18px_45px_-34px_rgba(74,30,40,0.35)] ring-1 ring-ink/5 sm:p-8';
    $sectionTitle = 'font-serif text-2xl leading-tight font-medium text-burgundy';
@endphp

<x-store-layout title="Libro de Reclamaciones" description="Libro de Reclamaciones virtual de {{ $settings->store_name }}, conforme al Código de Protección y Defensa del Consumidor.">
    <section class="bg-cream pt-5 sm:pt-7" aria-labelledby="complaints-title">
        <div class="store-container">
            <div class="relative isolate min-h-56 overflow-hidden rounded-[2rem] bg-blush shadow-[0_18px_45px_-32px_rgba(23,23,23,0.4)] sm:min-h-64">
                <img src="{{ asset('images/moods/flower-corner.webp') }}" alt="" aria-hidden="true" width="720" height="900" class="pointer-events-none absolute -right-8 -bottom-10 h-[85%] w-auto opacity-60 select-none sm:-right-4 sm:-bottom-16 sm:h-[135%] sm:opacity-100">

                <div class="relative z-10 flex min-h-56 max-w-2xl flex-col justify-center px-5 py-8 sm:min-h-64 sm:px-9 sm:py-10 lg:px-12">
                    <nav class="text-[0.68rem] text-burgundy/65" aria-label="Ruta">
                        <a href="{{ route('home') }}" class="transition-colors hover:text-burgundy">Inicio</a>
                        <span class="mx-1.5" aria-hidden="true">/</span>
                        <span aria-current="page">Libro de Reclamaciones</span>
                    </nav>

                    <p class="mt-5 text-[0.55rem] font-semibold tracking-[0.24em] text-burgundy/70 uppercase">Estamos para escucharte</p>
                    <h1 id="complaints-title" class="mt-1 max-w-[14ch] text-balance font-serif text-[clamp(2.15rem,1.6rem+2.3vw,3.5rem)] leading-[0.98] font-medium tracking-[-0.04em] text-burgundy">Libro de Reclamaciones</h1>
                    <p class="mt-3 max-w-md text-sm leading-relaxed text-ink/65 sm:text-base">Conforme al Código de Protección y Defensa del Consumidor.</p>

                    <p class="mt-5 inline-flex w-fit items-center rounded-full bg-cream/75 px-3 py-1.5 text-xs font-semibold text-burgundy backdrop-blur-sm">
                        Respuesta en máximo {{ Complaint::RESPONSE_DAYS }} días calendario
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="store-container max-w-3xl pt-8 sm:pt-10">
        @if ($filed)
            <section class="{{ $sectionCard }}" role="status" aria-labelledby="receipt-title">
                <div class="flex flex-col items-center text-center">
                    <span class="grid size-14 place-items-center rounded-full bg-blush text-burgundy">
                        <x-store.icon name="check" class="size-7" />
                    </span>
                    <h2 id="receipt-title" class="mt-4 font-serif text-2xl leading-tight font-medium text-burgundy sm:text-3xl">Registramos tu hoja de reclamación N° {{ $filed->code() }}</h2>
                    <p class="mt-2 max-w-md text-sm text-ink/70">Esta es tu constancia. También enviamos una copia a {{ $filed->email }}.</p>
                </div>

                <dl class="mt-8 grid gap-x-6 gap-y-3 rounded-3xl bg-cream/70 p-5 text-sm sm:grid-cols-[11rem_1fr] sm:p-6">
                    <dt class="text-ink/55">Fecha y hora</dt>
                    <dd>{{ $filed->created_at->format('d/m/Y \a \l\a\s h:i a') }}</dd>
                    <dt class="text-ink/55">Proveedor</dt>
                    <dd>{{ $provider }}@if ($settings->ruc) · RUC {{ $settings->ruc }}@endif</dd>
                    <dt class="text-ink/55">Consumidor</dt>
                    <dd>{{ $filed->consumer_name }} · {{ $filed->document_type->getLabel() }} {{ $filed->document_number }}</dd>
                    <dt class="text-ink/55">Correo</dt>
                    <dd class="break-all">{{ $filed->email }}</dd>
                    <dt class="text-ink/55">Tipo</dt>
                    <dd>{{ $filed->type->getLabel() }}</dd>
                    <dt class="text-ink/55">Producto o servicio</dt>
                    <dd>{{ $filed->item_type->getLabel() }}: {{ $filed->item_description }}</dd>
                    <dt class="text-ink/55">Monto reclamado</dt>
                    <dd>{{ $filed->amount !== null ? Price::format($filed->amount) : 'No indicado' }}</dd>
                    <dt class="text-ink/55">Detalle</dt>
                    <dd class="whitespace-pre-line">{{ $filed->detail }}</dd>
                    <dt class="text-ink/55">Pedido</dt>
                    <dd class="whitespace-pre-line">{{ $filed->consumer_request }}</dd>
                    <dt class="text-ink/55">Respuesta</dt>
                    <dd>Te responderemos por correo hasta el {{ $filed->responseDueDate()->format('d/m/Y') }}.</dd>
                </dl>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <a href="{{ $filed->receiptUrl() }}" class="btn-sage">Descargar constancia (PDF)</a>
                    <a href="{{ route('complaints.create') }}" class="btn-outline">Registrar otra hoja</a>
                </div>
            </section>
        @else
            <form method="post" action="{{ route('complaints.store') }}" class="space-y-6 sm:space-y-8" novalidate>
                @csrf

                <div class="{{ $sectionCard }}">
                    <fieldset>
                        <legend class="{{ $sectionTitle }}">1. Identificación del proveedor</legend>

                        <dl class="mt-5 grid gap-x-6 gap-y-2 rounded-3xl bg-blush/60 p-5 text-sm sm:grid-cols-[auto_1fr]">
                            <dt class="text-ink/55">Razón social</dt>
                            <dd class="font-medium">{{ $provider }}</dd>
                            @if ($settings->ruc)
                                <dt class="text-ink/55">RUC</dt>
                                <dd>{{ $settings->ruc }}</dd>
                            @endif
                            @if ($settings->legal_address)
                                <dt class="text-ink/55">Dirección</dt>
                                <dd>{{ $settings->legal_address }}</dd>
                            @endif
                        </dl>
                    </fieldset>
                </div>

                <div class="{{ $sectionCard }}">
                    <fieldset class="space-y-5">
                        <legend class="{{ $sectionTitle }}">2. Identificación del consumidor</legend>

                        <x-store.field name="consumer_name" label="Nombre completo" autocomplete="name" class="pt-5" required />

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

                        <div class="rounded-2xl bg-cream/70 p-4">
                            <label class="flex cursor-pointer items-center gap-3 text-sm">
                                <input type="hidden" name="is_minor" value="0">
                                <input type="checkbox" name="is_minor" value="1" class="size-4 accent-burgundy" data-toggles="guardian" @checked(old('is_minor'))>
                                Soy menor de edad
                            </label>
                            <div class="mt-4" data-toggled="guardian" @unless (old('is_minor')) hidden @endunless>
                                <x-store.field name="guardian_name" label="Nombre del padre, madre o apoderado" />
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="{{ $sectionCard }}">
                    <fieldset class="space-y-6">
                        <legend class="{{ $sectionTitle }}">3. Información del reclamo o queja</legend>

                        <div class="pt-5">
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach (ComplaintType::cases() as $complaintType)
                                    <label class="flex cursor-pointer gap-3 rounded-3xl border border-ink/10 bg-white p-4 transition hover:border-burgundy/30 has-checked:border-burgundy/60 has-checked:bg-blush/60 has-focus-visible:ring-4 has-focus-visible:ring-burgundy/10 sm:p-5">
                                        <input type="radio" name="type" value="{{ $complaintType->value }}" class="mt-0.5 size-4 shrink-0 accent-burgundy" @checked(old('type', ComplaintType::Claim->value) === $complaintType->value)>
                                        <span class="text-sm">
                                            <span class="block font-serif text-lg leading-tight font-medium text-burgundy">{{ $complaintType->getLabel() }}</span>
                                            <span class="mt-1 block leading-relaxed text-ink/65">{{ $complaintType->getDescription() }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('type') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="filed_on" class="form-label">Fecha de presentación</label>
                                <input id="filed_on" type="text" value="{{ now()->format('d/m/Y') }}" class="form-input cursor-default bg-cream/70 text-ink/70" readonly>
                            </div>

                            <div>
                                <p class="form-label">¿Es un producto o un servicio?</p>
                                <div class="mt-1.5 grid grid-cols-2 gap-1 rounded-2xl bg-cream/80 p-1">
                                    @foreach (ComplaintItemType::cases() as $itemType)
                                        <label class="flex cursor-pointer items-center justify-center rounded-xl px-3 py-2.5 text-sm font-medium text-ink/60 transition has-checked:bg-white has-checked:text-burgundy has-checked:shadow-sm has-focus-visible:ring-2 has-focus-visible:ring-burgundy/30">
                                            <input type="radio" name="item_type" value="{{ $itemType->value }}" class="sr-only" @checked(old('item_type', ComplaintItemType::Product->value) === $itemType->value)>
                                            {{ $itemType->getLabel() }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('item_type') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-[1fr_12rem]">
                            <x-store.field name="item_description" label="Producto o servicio" placeholder="Producto, pedido o servicio" required />
                            <x-store.field name="amount" label="Monto reclamado (S/)" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00" hint="Opcional." />
                        </div>

                        <x-store.field name="detail" label="Descripción de los hechos" type="textarea" maxlength="3000" placeholder="Explica qué ocurrió, incluyendo fechas o número de pedido si corresponde." required />
                        <x-store.field name="consumer_request" label="Pedido concreto del consumidor" type="textarea" rows="3" maxlength="2000" placeholder="Indica qué solución solicitas." required />
                    </fieldset>
                </div>

                <div class="{{ $sectionCard }} space-y-5">
                    <div>
                        <label class="flex cursor-pointer items-start gap-3 text-sm leading-relaxed">
                            <input type="checkbox" name="privacy" value="1" class="mt-1 size-4 shrink-0 accent-burgundy" @checked(old('privacy')) @error('privacy') aria-invalid="true" @enderror>
                            <span>Declaro que los datos ingresados son correctos y he leído la información sobre el tratamiento de mis datos personales.</span>
                        </label>
                        @error('privacy') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <details class="group rounded-2xl bg-cream/70 px-4 py-3 text-xs leading-relaxed text-ink/70">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 py-1 text-sm font-medium text-burgundy [&::-webkit-details-marker]:hidden">
                            Tratamiento de tus datos personales
                            <x-store.icon name="chevron-down" class="size-4 shrink-0 transition-transform duration-200 group-open:rotate-180 motion-reduce:transition-none" />
                        </summary>
                        <div class="mt-2 space-y-2 pb-1">
                            <p>{{ $provider }}@if ($settings->ruc) (RUC {{ $settings->ruc }})@endif es responsable de los datos que ingresas en esta hoja, conforme a la Ley N° 29733, Ley de Protección de Datos Personales.</p>
                            <p>Los usamos solo para registrar y responder tu reclamo o queja, y para cumplir lo que exige la norma del Libro de Reclamaciones. Los conservamos al menos dos años, como pide esa norma, y solo los compartimos con el INDECOPI u otra autoridad si lo solicita.</p>
                            <p>Puedes pedir acceder a tus datos, corregirlos o cancelarlos{{ $settings->email ? " escribiendo a {$settings->email}" : '' }}.</p>
                        </div>
                    </details>

                    <div class="space-y-2 text-xs leading-relaxed text-ink/55">
                        <p>La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.</p>
                        <p>El proveedor deberá dar respuesta al reclamo o queja en un plazo no mayor a {{ Complaint::RESPONSE_DAYS }} días calendario.</p>
                    </div>

                    <button type="submit" class="btn-sage min-h-12 w-full px-8 sm:w-auto">Enviar hoja de reclamación</button>
                </div>
            </form>
        @endif
    </div>
</x-store-layout>
