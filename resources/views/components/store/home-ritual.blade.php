<section class="relative isolate overflow-hidden bg-blush py-10 sm:py-12 lg:py-14" aria-labelledby="como-funciona-title">
    <img src="{{ asset('images/moods/flower-corner.webp') }}" alt="" aria-hidden="true" width="720" height="900" class="pointer-events-none absolute -top-10 -left-10 -z-10 h-56 w-auto rotate-180 opacity-50 select-none sm:h-80 lg:-top-16 lg:h-96 lg:opacity-70">
    <img src="{{ asset('images/moods/flower-corner.webp') }}" alt="" aria-hidden="true" width="720" height="900" class="pointer-events-none absolute -right-10 -bottom-10 -z-10 h-64 w-auto opacity-50 select-none sm:h-96 lg:-bottom-16 lg:h-[30rem] lg:opacity-70">

    <div class="store-container">
        <div class="mx-auto max-w-xl text-center">
            <p class="text-[0.5rem] font-semibold tracking-[0.24em] text-emerald uppercase">Tu ritual Hade</p>
            <h2 id="como-funciona-title" class="mt-1 font-serif text-[clamp(1.85rem,1.35rem+2vw,2.75rem)] leading-tight font-medium tracking-[-0.03em] text-ink">Así de fácil. Así de Hade.</h2>
            <p class="mt-2 text-sm text-ink/70">Una manicure con acabado impecable, desde casa y en pocos minutos.</p>
        </div>

        <div class="mx-auto mt-8 max-w-5xl sm:mt-10">
            <ol class="grid grid-cols-2 gap-x-5 gap-y-10 sm:gap-x-8 sm:gap-y-12 lg:grid-cols-4 lg:gap-x-6">
                @foreach ([
                        ['number' => '01', 'title' => 'Prepara', 'description' => 'Limpia y seca tus uñas; empuja suavemente las cutículas.'],
                        ['number' => '02', 'title' => 'Elige y pega', 'description' => 'Escoge el tamaño correcto, colócalo desde la base y alisa hacia los bordes.'],
                        ['number' => '03', 'title' => 'Recorta y lima', 'description' => 'Retira el excedente y lima hacia abajo siguiendo la forma de tu uña.'],
                        ['number' => '04', 'title' => 'Cura y disfruta', 'description' => 'Pasa cada uña por la lámpara para fijar el gel. Tu diseño está listo.'],
                    ] as $step)
                    <li @class(['group text-center', 'mt-8 lg:mt-14' => $loop->even])>
                        <div @class([
                                'ritual-shape mx-auto aspect-[5/6] w-full max-w-56 overflow-hidden shadow-[0_22px_45px_-28px_rgba(110,30,50,0.5)] transition-transform duration-700 ease-out group-hover:rotate-0 motion-reduce:transition-none',
                                '-rotate-3' => $loop->odd,
                                'rotate-3' => $loop->even,
                            ]) style="--ritual-delay: -{{ $loop->index * 3.5 }}s">
                            <img src="{{ asset('images/ritual/step-'.$loop->iteration.'.jpg') }}" alt="" loading="lazy" class="size-full object-cover transition duration-700 group-hover:scale-105 motion-reduce:transition-none">
                        </div>

                        <div class="mt-6">
                            <p class="font-serif text-xl leading-none text-burgundy/60 italic"><span class="sr-only">Paso </span>{{ $step['number'] }}</p>
                            <h3 class="mt-1.5 font-serif text-lg leading-tight font-medium text-ink sm:text-xl">{{ $step['title'] }}</h3>
                            <p class="mx-auto mt-1.5 max-w-[15rem] text-xs leading-relaxed text-ink/70 sm:text-sm">{{ $step['description'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="mt-10 flex justify-center lg:mt-12">
            <a href="{{ route('products.index') }}" class="group inline-flex min-h-10 items-center justify-center gap-2 rounded-full bg-burgundy px-5 text-xs font-semibold text-white transition duration-300 hover:bg-burgundy-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-burgundy sm:text-sm">
                Encuentra tu diseño
                <x-store.icon name="chevron-right" class="size-3.5 transition-transform duration-300 group-hover:translate-x-0.5 motion-reduce:transition-none motion-reduce:group-hover:translate-x-0" />
            </a>
        </div>
    </div>
</section>
