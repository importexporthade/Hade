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
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El nombre de tu tienda. Se ve en la cabecera en celulares, al pie de cada página, en la pestaña del navegador y al compartir un enlace por WhatsApp o redes.')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('announcement')
                            ->label('Aviso superior')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Una frase corta en la franja de color sobre el menú, en todas las páginas. Úsala para avisos como envíos gratis o una promoción. Si la dejas vacía, la franja no aparece.')
                            ->helperText('Franja sobre el menú, p. ej. "Delivery gratis a todo el Perú".')
                            ->maxLength(255),
                        PhotoUpload::make('logo', ImageKind::Logo)
                            ->label('Logo'),
                        PhotoUpload::make('header_image', ImageKind::Header)
                            ->label('Imagen de cabecera')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La foto de fondo de la pantalla para entrar al manager, la franja que se ve detrás de la caja de correo y contraseña.')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Tu logo. Se usa como imagen cuando alguien comparte por WhatsApp o redes un enlace de la tienda que no tiene foto propia, como la portada.')
                            ->helperText('La franja de fotos sobre el logo.'),
                    ]),
                Section::make('Pedidos por WhatsApp')
                    ->schema([
                        TextInput::make('whatsapp_number')
                            ->label('Número de WhatsApp')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El número al que llegan los pedidos: el botón verde de WhatsApp y el botón para pedir del carrito abren un chat con él. Si lo dejas vacío, el botón verde no aparece.')
                            ->helperText('Con código de país, p. ej. 51 986 000 362.')
                            ->tel()
                            ->regex('/^\+?[\d\s-]{8,20}$/')
                            ->maxLength(20),
                        TextInput::make('whatsapp_message')
                            ->label('Mensaje inicial')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El texto que ya viene escrito cuando la clienta abre el chat con el botón verde de WhatsApp. Ella puede cambiarlo antes de enviarlo.')
                            ->placeholder('Hola. Me gustaría comprar un producto de tu tienda.')
                            ->maxLength(255),
                    ]),
                Section::make('Contacto')
                    ->schema([
                        TextInput::make('email')
                            ->label('Correo')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Tu correo de contacto. Se muestra en el pie de página y en el Libro de Reclamaciones, y ahí te llegan los avisos de reclamos nuevos.')
                            ->helperText('También aquí llegan los avisos del Libro de Reclamaciones.')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Un teléfono de contacto que se muestra en el pie de página y en las hojas de reclamación. Es opcional.')
                            ->tel()
                            ->maxLength(30),
                        Textarea::make('footer_text')
                            ->label('Texto del pie de página')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Un párrafo sobre la tienda al pie de todas las páginas, junto al logo. Google y WhatsApp también lo usan como descripción de la tienda.')
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
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La dirección completa de tu página de Facebook, empezando por https://. Aparece como ícono en el pie de página de la tienda; si la dejas vacía, el ícono no aparece.')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('instagram_url')
                            ->label('Instagram')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La dirección completa de tu página de Instagram, empezando por https://. Aparece como ícono en el pie de página de la tienda; si la dejas vacía, el ícono no aparece.')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('tiktok_url')
                            ->label('TikTok')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La dirección completa de tu página de TikTok, empezando por https://. Aparece como ícono en el pie de página de la tienda; si la dejas vacía, el ícono no aparece.')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('youtube_url')
                            ->label('YouTube')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La dirección completa de tu página de YouTube, empezando por https://. Aparece como ícono en el pie de página de la tienda; si la dejas vacía, el ícono no aparece.')
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
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El nombre legal de la empresa o de la persona dueña, tal como figura en SUNAT. Se imprime en cada hoja del Libro de Reclamaciones.')
                            ->maxLength(255),
                        TextInput::make('ruc')
                            ->label('RUC')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El RUC de 11 dígitos de la empresa o de la persona dueña. Se imprime en cada hoja del Libro de Reclamaciones.')
                            ->regex('/^\d{11}$/')
                            ->validationMessages(['regex' => 'El RUC tiene 11 dígitos.'])
                            ->maxLength(11),
                        TextInput::make('legal_address')
                            ->label('Dirección fiscal')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La dirección registrada en SUNAT. Se imprime en cada hoja del Libro de Reclamaciones.')
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
