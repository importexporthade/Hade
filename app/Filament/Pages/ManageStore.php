<?php

namespace App\Filament\Pages;

use App\Enums\ImageKind;
use App\Filament\Support\PhotoUpload;
use App\Models\StoreSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageStore extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Tienda online';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Datos de la tienda';

    protected static ?string $title = 'Datos de la tienda';

    protected static ?string $slug = 'tienda';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(StoreSetting::current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(2)
            ->components([
                Section::make('Marca')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('store_name')
                            ->label('Nombre de la tienda')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El nombre de tu tienda. Tus clientas lo verán en la web y cuando alguien comparta un enlace tuyo. Ej: Hade.')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('announcement')
                            ->label('Aviso superior')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Un mensaje corto que todas verán en la franja de arriba, ideal para una promo o un aviso. Ej: Envío gratis a todo el Perú.')
                            ->helperText('Franja sobre el menú, p. ej. "Delivery gratis a todo el Perú".')
                            ->maxLength(255),
                        PhotoUpload::make('logo', ImageKind::Logo)
                            ->label('Logo')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La imagen que aparece cuando alguien comparte tu tienda por WhatsApp o redes. El logo de la web no cambia con esto. Ej: tu logo cuadrado.'),
                        PhotoUpload::make('header_image', ImageKind::Header)
                            ->label('Imagen de cabecera')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La foto que ves arriba cuando entras al manager. Es solo para ti: la tienda no la usa.'),
                    ]),
                Section::make('Pedidos por WhatsApp')
                    ->schema([
                        TextInput::make('whatsapp_number')
                            ->label('Número de WhatsApp')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Aquí te llegan los pedidos cuando una clienta toca el botón de WhatsApp. Escríbelo con el código de país. Ej: 51 986 000 362.')
                            ->helperText('Con código de país, p. ej. 51 986 000 362.')
                            ->tel()
                            ->regex('/^\+?[\d\s-]{8,20}$/')
                            ->maxLength(20),
                        TextInput::make('whatsapp_message')
                            ->label('Mensaje inicial')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El mensaje que ya aparece escrito cuando la clienta abre el chat contigo, así solo tiene que enviarlo. Ej: Hola, quiero hacer un pedido.')
                            ->placeholder('Hola. Me gustaría comprar un producto de tu tienda.')
                            ->maxLength(255),
                    ]),
                Section::make('Contacto')
                    ->schema([
                        TextInput::make('email')
                            ->label('Correo')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Tus clientas lo verán al pie de la página para escribirte, y aquí te avisamos de cada reclamo nuevo. Ej: hola@hade.pe.')
                            ->helperText('También aquí llegan los avisos del Libro de Reclamaciones.')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Por si alguna clienta prefiere llamarte; aparece al pie de la página. Si no quieres llamadas, déjalo vacío. Ej: 01 234 5678.')
                            ->tel()
                            ->maxLength(30),
                        Textarea::make('footer_text')
                            ->label('Texto del pie de página')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Unas líneas para contar quién eres; aparecen al final de cada página. Ej: Stickers para uñas hechos con cariño en Perú.')
                            ->helperText('Un párrafo sobre la tienda.')
                            ->rows(4)
                            ->maxLength(1000),
                    ]),
                Section::make('Redes sociales')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('facebook_url')
                            ->label('Facebook')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Pega aquí el enlace de tu página y aparecerá un botón al pie de la tienda para que te sigan. Ej: https://facebook.com/hade.pe')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('instagram_url')
                            ->label('Instagram')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Pega aquí el enlace de tu perfil y aparecerá un botón al pie de la tienda para que te sigan. Ej: https://instagram.com/hade.pe')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('tiktok_url')
                            ->label('TikTok')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Pega aquí el enlace de tu perfil y aparecerá un botón al pie de la tienda para que te sigan. Ej: https://tiktok.com/@hade.pe')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('youtube_url')
                            ->label('YouTube')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Pega aquí el enlace de tu canal y aparecerá un botón al pie de la tienda para que te sigan. Ej: https://youtube.com/@hade')
                            ->url()
                            ->maxLength(255),
                    ]),
                Section::make('Envíos y delivery')
                    ->description('Cada uno se muestra en la página de los productos que tengan prendido «Envíos a Lima» o «Envíos a provincia».')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('shipping_capital')
                            ->label('Envíos a capital')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Lo que verán tus clientas de Lima: cuánto cuesta, cuánto tarda y cómo les llega. Ej: Delivery S/ 10, llega en 24 a 48 horas.')
                            ->rows(4)
                            ->maxLength(1000),
                        Textarea::make('shipping_province')
                            ->label('Envíos a provincia')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Lo que verán tus clientas fuera de Lima: por qué agencia va, cuánto cuesta y cuánto tarda. Ej: Por Shalom desde S/ 15, llega en 2 a 4 días.')
                            ->rows(4)
                            ->maxLength(1000),
                    ]),
                Section::make('Datos legales')
                    ->description('Se imprimen en cada hoja del Libro de Reclamaciones, como pide Indecopi.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('legal_name')
                            ->label('Razón social o nombre del titular')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Tu nombre o el de tu empresa, tal cual figura en SUNAT. Aparece al pie y en el Libro de Reclamaciones. Ej: Hade S.A.C.')
                            ->maxLength(255),
                        TextInput::make('ruc')
                            ->label('RUC')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Tu RUC de 11 dígitos. Aparece en la tienda para que tus clientas sepan quién les vende. Ej: 20123456789.')
                            ->regex('/^\d{11}$/')
                            ->validationMessages(['regex' => 'El RUC tiene 11 dígitos.'])
                            ->maxLength(11),
                        TextInput::make('legal_address')
                            ->label('Dirección fiscal')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La dirección que tienes registrada en SUNAT. Aparece al pie y en el Libro de Reclamaciones. Ej: Av. Larco 123, Miraflores.')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Guardar')
                                ->submit('save'),
                        ])->sticky(),
                    ]),
            ]);
    }

    public function save(): void
    {
        StoreSetting::current()->update($this->form->getState());

        Notification::make()
            ->success()
            ->title('Datos de la tienda guardados')
            ->send();
    }
}
