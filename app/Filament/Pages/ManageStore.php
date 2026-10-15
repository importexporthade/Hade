<?php

namespace App\Filament\Pages;

use App\Filament\Support\PhotoUpload;
use App\Models\StoreSetting;
use App\Support\ImageOptimizer;
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
                            ->required()
                            ->maxLength(255),
                        TextInput::make('announcement')
                            ->label('Aviso superior')
                            ->helperText('Franja sobre el menú, p. ej. "Delivery gratis a todo el Perú".')
                            ->maxLength(255),
                        PhotoUpload::make('logo', 'store', ImageOptimizer::LOGO_SIZE)
                            ->label('Logo'),
                        PhotoUpload::make('header_image', 'store', ImageOptimizer::HEADER_SIZE)
                            ->label('Imagen de cabecera')
                            ->helperText('La franja de fotos sobre el logo.'),
                    ]),
                Section::make('Pedidos por WhatsApp')
                    ->schema([
                        TextInput::make('whatsapp_number')
                            ->label('Número de WhatsApp')
                            ->helperText('Con código de país, p. ej. 51 986 000 362.')
                            ->tel()
                            ->regex('/^\+?[\d\s-]{8,20}$/')
                            ->maxLength(20),
                        TextInput::make('whatsapp_message')
                            ->label('Mensaje inicial')
                            ->placeholder('Hola. Me gustaría comprar un producto de tu tienda.')
                            ->maxLength(255),
                    ]),
                Section::make('Contacto')
                    ->schema([
                        TextInput::make('email')
                            ->label('Correo')
                            ->helperText('También aquí llegan los avisos del Libro de Reclamaciones.')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(30),
                        Textarea::make('footer_text')
                            ->label('Texto del pie de página')
                            ->helperText('Un párrafo sobre la tienda.')
                            ->rows(4)
                            ->maxLength(1000),
                    ]),
                Section::make('Redes sociales')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('facebook_url')->label('Facebook')->url()->maxLength(255),
                        TextInput::make('instagram_url')->label('Instagram')->url()->maxLength(255),
                        TextInput::make('tiktok_url')->label('TikTok')->url()->maxLength(255),
                        TextInput::make('youtube_url')->label('YouTube')->url()->maxLength(255),
                    ]),
                Section::make('Datos legales')
                    ->description('Se imprimen en cada hoja del Libro de Reclamaciones, como pide Indecopi.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('legal_name')
                            ->label('Razón social o nombre del titular')
                            ->maxLength(255),
                        TextInput::make('ruc')
                            ->label('RUC')
                            ->regex('/^\d{11}$/')
                            ->validationMessages(['regex' => 'El RUC tiene 11 dígitos.'])
                            ->maxLength(11),
                        TextInput::make('legal_address')
                            ->label('Dirección fiscal')
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
                        ]),
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
