<?php

namespace App\Filament\Resources\Complaints;

use App\Filament\Resources\Complaints\Pages\ListComplaints;
use App\Filament\Resources\Complaints\Pages\ViewComplaint;
use App\Filament\Resources\Complaints\Schemas\ComplaintInfolist;
use App\Filament\Resources\Complaints\Tables\ComplaintsTable;
use App\Models\Complaint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * The complaints book as the owner sees it: sheets are read and answered here, never edited
 * or deleted, since the law requires keeping them as filed.
 */
class ComplaintResource extends Resource
{
    protected static ?string $model = Complaint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Atención al cliente';

    protected static ?string $navigationLabel = 'Libro de Reclamaciones';

    protected static ?string $modelLabel = 'hoja de reclamación';

    protected static ?string $pluralModelLabel = 'Libro de Reclamaciones';

    protected static ?string $recordTitleAttribute = 'consumer_name';

    public static function infolist(Schema $schema): Schema
    {
        return ComplaintInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComplaintsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * Sheets still waiting for an answer.
     */
    public static function getNavigationBadge(): ?string
    {
        $pending = Complaint::query()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return 'Por responder';
    }

    public static function getBreadcrumb(): string
    {
        return 'Libro de Reclamaciones';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComplaints::route('/'),
            'view' => ViewComplaint::route('/{record}'),
        ];
    }
}
