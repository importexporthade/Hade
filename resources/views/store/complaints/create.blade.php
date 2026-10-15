@php
    use App\Enums\ComplaintItemType;
    use App\Enums\ComplaintType;
    use App\Enums\IdentityDocument;
    use App\Models\Complaint;
@endphp

<x-store-layout title="Libro de Reclamaciones" description="Libro de Reclamaciones virtual de {{ $settings->store_name }}, conforme al Código de Protección y Defensa del Consumidor.">
    <div class="store-container max-w-3xl pt-10">
        <div class="flex items-center gap-3">
            <x-store.icon name="book" class="size-9 text-emerald" />
            <h1 class="font-serif text-3xl">Libro de Reclamaciones</h1>
        </div>

        <dl class="mt-6 grid gap-x-6 gap-y-2 border border-ink/10 bg-white p-5 text-sm sm:grid-cols-[auto_1fr]">
            <dt class="text-ink/60">Proveedor</dt>
            <dd class="font-medium">{{ $settings->legal_name ?: $settings->store_name }}</dd>
            @if ($settings->ruc)
                <dt class="text-ink/60">RUC</dt>
                <dd>{{ $settings->ruc }}</dd>
            @endif
            @if ($settings->legal_address)
                <dt class="text-ink/60">Dirección</dt>
                <dd>{{ $settings->legal_address }}</dd>
            @endif
            <dt class="text-ink/60">Fecha</dt>
            <dd>{{ now()->format('d/m/Y') }}</dd>
        </dl>

        @if (session('complaint_code'))
            <div class="mt-6 flex gap-3 border border-emerald/30 bg-sage-light p-5" role="status">
                <x-store.icon name="check" class="size-6 shrink-0 text-emerald" />
                <div class="text-sm">
                    <p class="font-semibold text-emerald">Registramos tu hoja de reclamación N° {{ session('complaint_code') }}.</p>
                    <p class="mt-1 text-ink/80">Enviamos una copia a {{ session('complaint_email') }} y te responderemos en un plazo máximo de {{ Complaint::RESPONSE_BUSINESS_DAYS }} días hábiles.</p>
                </div>
            </div>
        @endif

        <form method="post" action="{{ route('complaints.store') }}" class="mt-8 space-y-10" novalidate>
            @csrf

            <fieldset class="space-y-5">
                <legend class="text-lg font-bold">1. Tus datos</legend>

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

                <x-store.field name="address" label="Domicilio" autocomplete="street-address" required />

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-store.field name="phone" label="Teléfono" type="tel" autocomplete="tel" required />
                    <x-store.field name="email" label="Correo electrónico" type="email" autocomplete="email" hint="Aquí te llegará la copia y nuestra respuesta." required />
                </div>

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
                <legend class="text-lg font-bold">2. Producto o servicio</legend>

                <div class="flex gap-6">
                    @foreach (ComplaintItemType::cases() as $itemType)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="item_type" value="{{ $itemType->value }}" class="size-4 accent-emerald" @checked(old('item_type', ComplaintItemType::Product->value) === $itemType->value)>
                            {{ $itemType->getLabel() }}
                        </label>
                    @endforeach
                </div>
                @error('item_type') <p class="form-error">{{ $message }}</p> @enderror

                <div class="grid gap-5 sm:grid-cols-[1fr_12rem]">
                    <x-store.field name="item_description" label="¿Qué producto o servicio?" placeholder="Kit Hade Recargada" required />
                    <x-store.field name="amount" label="Monto reclamado (S/)" type="number" step="0.01" min="0" inputmode="decimal" hint="Opcional." />
                </div>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="text-lg font-bold">3. Detalle</legend>

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

                <x-store.field name="detail" label="Cuéntanos qué pasó" type="textarea" maxlength="3000" required />
                <x-store.field name="consumer_request" label="¿Qué solicitas?" type="textarea" rows="3" maxlength="2000" placeholder="Por ejemplo: el cambio del producto o la devolución del dinero." required />
            </fieldset>

            <div class="space-y-3 border-t border-ink/10 pt-6 text-xs leading-relaxed text-ink/60">
                <p>La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.</p>
                <p>El proveedor deberá dar respuesta al reclamo o queja en un plazo no mayor a {{ Complaint::RESPONSE_BUSINESS_DAYS }} días hábiles.</p>
                <p>Usamos tus datos solo para atender esta hoja de reclamación.</p>
            </div>

            <button type="submit" class="btn-sage w-full sm:w-auto">Enviar hoja de reclamación</button>
        </form>
    </div>
</x-store-layout>
