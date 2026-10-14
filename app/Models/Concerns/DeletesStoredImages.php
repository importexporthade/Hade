<?php

namespace App\Models\Concerns;

use App\Support\ImageOptimizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Removes uploaded photos from the public disk when the manager replaces or removes them,
 * or deletes the record that owned them, so storage only holds photos still in use.
 */
trait DeletesStoredImages
{
    /**
     * Attributes holding a public-disk path, or a list of paths.
     *
     * @return list<string>
     */
    abstract protected function storedImageAttributes(): array;

    protected static function bootDeletesStoredImages(): void
    {
        static::updated(function (Model $model): void {
            $model->deleteStoredImages(array_diff(
                $model->storedImagePaths(fn (string $attribute) => $model->getOriginal($attribute)),
                $model->storedImagePaths(fn (string $attribute) => $model->getAttribute($attribute)),
            ));
        });

        static::deleted(function (Model $model): void {
            $model->deleteStoredImages(
                $model->storedImagePaths(fn (string $attribute) => $model->getAttribute($attribute)),
            );
        });
    }

    /**
     * @param  callable(string): mixed  $valueOf
     * @return list<string>
     */
    protected function storedImagePaths(callable $valueOf): array
    {
        return collect($this->storedImageAttributes())
            ->flatMap(fn (string $attribute) => Arr::wrap($valueOf($attribute)))
            ->filter(fn (mixed $path) => is_string($path) && $path !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function deleteStoredImages(array $paths): void
    {
        if ($paths !== []) {
            $thumbnails = array_map(ImageOptimizer::thumbnailPath(...), $paths);

            Storage::disk('public')->delete([...array_values($paths), ...array_values($thumbnails)]);
        }
    }
}
