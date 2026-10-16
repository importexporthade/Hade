<?php

namespace App\Filament\Support;

use App\Enums\ImageKind;
use App\Support\ImageOptimizer;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * A photo field that stores what the owner uploads as optimized WebP with its smaller
 * copies, so the storefront stays fast whatever size the original photo had.
 */
class PhotoUpload
{
    public static function make(string $name, ImageKind $kind): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->imageEditor()
            ->maxSize(10240)
            ->disk('public')
            ->directory($kind->directory())
            ->visibility('public')
            ->saveUploadedFileUsing(fn (BaseFileUpload $component, TemporaryUploadedFile $file): ?string => ImageOptimizer::store($file->get(), $kind)
                ?? $component->saveUploadedFile($file));
    }
}
