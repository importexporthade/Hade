<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\PathTraversalDetected;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Hands out the photos uploaded from the manager (/storage/products/…), because the host
 * cannot make the public/storage link the web server would use to send them itself. Each
 * photo has a name of its own, so browsers and the CDN may keep it for a year.
 */
class StoredPhotoController extends Controller
{
    /**
     * Only pictures are ever handed out, whatever else the folder may hold.
     */
    private const array PHOTO_EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png', 'gif', 'avif'];

    public function __invoke(string $path): BinaryFileResponse
    {
        abort_unless(in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::PHOTO_EXTENSIONS, true), 404);

        $disk = Storage::disk('public');

        try {
            abort_unless($disk->fileExists($path), 404);
        } catch (PathTraversalDetected) {
            abort(404);
        }

        return response()->file($disk->path($path), [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
