<?php

namespace App\Filament\Support;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * The "Enlace" field: the last part of a storefront address. It fills itself from the name
 * while the owner hasn't written their own, and only ever keeps lowercase letters without
 * accents, numbers and single dashes, so the address always works when it is shared.
 */
class SlugInput
{
    public const string PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * Spaces and underscores become dashes and anything else that isn't a plain letter or a
     * number disappears as the owner types; the server cleans it again when the field loses focus.
     */
    private const string CLEAN_WHILE_TYPING = <<<'JS'
        const input = $event.target;
        const typed = input.value;
        const cleaned = typed.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/[\s_]+/g, '-').replace(/[^a-z0-9-]/g, '').replace(/-{2,}/g, '-').replace(/^-/, '');

        if (cleaned !== typed) {
            const cursor = Math.max(0, input.selectionStart - (typed.length - cleaned.length));
            input.value = cleaned;
            input.setSelectionRange(cursor, cursor);
        }
        JS;

    /**
     * @param  string  $routeName  The storefront route the link opens, such as "products.show".
     * @param  string  $sampleSlug  Shown in the tooltip until the field has a link of its own.
     */
    public static function make(string $routeName, string $sampleSlug): TextInput
    {
        return TextInput::make('slug')
            ->label('Enlace')
            ->required()
            ->maxLength(255)
            ->regex(self::PATTERN)
            ->validationMessages(['regex' => 'Usa solo letras minúsculas sin tildes, números y guiones (-), sin espacios.'])
            ->unique(ignoreRecord: true)
            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: fn (?string $state): string => self::tooltip($routeName, $sampleSlug, $state))
            ->extraInputAttributes(['x-on:input' => self::CLEAN_WHILE_TYPING], merge: true)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, ?string $state): void {
                $set('slug', self::format($state));
            });
    }

    /**
     * For the name field: keeps the link in step with the name while creating, until the owner
     * writes a link of their own. Editing never touches it, so links already shared keep working.
     */
    public static function fillFromName(): Closure
    {
        return function (Get $get, Set $set, ?string $old, ?string $state, string $operation): void {
            if ($operation === 'edit') {
                return;
            }

            $current = (string) $get('slug');

            if ($current !== '' && $current !== self::format($old)) {
                return;
            }

            $set('slug', self::format($state));
        };
    }

    public static function format(?string $value): string
    {
        return Str::slug((string) $value, '-', 'es', []);
    }

    /**
     * The address the record will have with the link as it stands, so the owner sees what
     * they are sharing before saving.
     */
    private static function address(string $routeName, string $slug): string
    {
        return preg_replace('#^https?://(www\.)?#', '', route($routeName, $slug));
    }

    private static function tooltip(string $routeName, string $sampleSlug, ?string $slug): string
    {
        $slug = filled($slug) ? $slug : $sampleSlug;

        return 'Es el final del enlace y se llena solo con el nombre; casi nunca necesitas tocarlo. Ej: '.self::address($routeName, $slug);
    }
}
