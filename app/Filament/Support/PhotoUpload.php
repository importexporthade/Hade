<?php

namespace App\Filament\Support;

use App\Support\ImageOptimizer;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * A photo field that stores what the owner uploads as optimized WebP on the public disk,
 * so the storefront stays fast whatever size the original photo had.
 */
class PhotoUpload
{
    public static function make(string $name, string $directory, int $maxSize, ?int $thumbnailSize = null): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->imageEditor()
            ->maxSize(10240)
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->saveUploadedFileUsing(fn (BaseFileUpload $component, TemporaryUploadedFile $file): ?string => ImageOptimizer::store($file->get(), $directory, $maxSize, $thumbnailSize)
                ?? $component->saveUploadedFile($file));
    }
}
