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
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Cómo se llama tu tienda. Sale en la web y cuando compartes un enlace. Ej: Hade.')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('announcement')
                            ->label('Aviso superior')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Un aviso corto en la franja de arriba del menú. Ej: Envío gratis a todo el Perú.')
                            ->helperText('Franja sobre el menú, p. ej. "Delivery gratis a todo el Perú".')
                            ->maxLength(255),
                        PhotoUpload::make('logo', ImageKind::Logo)
                            ->label('Logo')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Tu logo. También es la imagen que sale al compartir la tienda por WhatsApp o redes.'),
                        PhotoUpload::make('header_image', ImageKind::Header)
                            ->label('Imagen de cabecera')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La foto de fondo de la pantalla donde entras al manager.')
                            ->helperText('La franja de fotos sobre el logo.'),
                    ]),
                Section::make('Pedidos por WhatsApp')
                    ->schema([
                        TextInput::make('whatsapp_number')
                            ->label('Número de WhatsApp')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'A este número te llegan los pedidos del botón de WhatsApp. Ej: 51 986 000 362.')
                            ->helperText('Con código de país, p. ej. 51 986 000 362.')
                            ->tel()
                            ->regex('/^\+?[\d\s-]{8,20}$/')
                            ->maxLength(20),
                        TextInput::make('whatsapp_message')
                            ->label('Mensaje inicial')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Lo primero que te escribe la clienta al abrir el chat. Ej: Hola, quiero hacer un pedido.')
                            ->placeholder('Hola. Me gustaría comprar un producto de tu tienda.')
                            ->maxLength(255),
                    ]),
                Section::make('Contacto')
                    ->schema([
                        TextInput::make('email')
                            ->label('Correo')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Tus clientas lo ven para escribirte, y aquí te llegan los reclamos. Ej: hola@hade.pe.')
                            ->helperText('También aquí llegan los avisos del Libro de Reclamaciones.')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Para que te llamen; sale al pie de la página. Ej: 01 234 5678.')
                            ->tel()
                            ->maxLength(30),
                        Textarea::make('footer_text')
                            ->label('Texto del pie de página')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Unas líneas sobre tu tienda al final de cada página. Ej: Stickers para uñas hechos en Perú.')
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
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Pega el enlace de tu página y su ícono saldrá al pie de la tienda. Ej: https://facebook.com/hade.pe')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('instagram_url')
                            ->label('Instagram')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Pega el enlace de tu perfil y su ícono saldrá al pie de la tienda. Ej: https://instagram.com/hade.pe')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('tiktok_url')
                            ->label('TikTok')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Pega el enlace de tu perfil y su ícono saldrá al pie de la tienda. Ej: https://tiktok.com/@hade.pe')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('youtube_url')
                            ->label('YouTube')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Pega el enlace de tu canal y su ícono saldrá al pie de la tienda. Ej: https://youtube.com/@hade')
                            ->url()
                            ->maxLength(255),
                    ]),
                Section::make('Datos legales')
                    ->description('Se imprimen en cada hoja del Libro de Reclamaciones, como pide Indecopi.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('legal_name')
                            ->label('Razón social o nombre del titular')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Tu nombre o el de tu empresa, tal como figura en SUNAT. Ej: Hade S.A.C.')
                            ->maxLength(255),
                        TextInput::make('ruc')
                            ->label('RUC')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Tu RUC de 11 dígitos, tal como figura en SUNAT. Ej: 20123456789.')
                            ->regex('/^\d{11}$/')
                            ->validationMessages(['regex' => 'El RUC tiene 11 dígitos.'])
                            ->maxLength(11),
                        TextInput::make('legal_address')
                            ->label('Dirección fiscal')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La dirección que tienes registrada en SUNAT. Ej: Av. Larco 123, Miraflores.')
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
                                ->label('Guardar cambios')
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
