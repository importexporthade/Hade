<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoredPhotoServingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_an_uploaded_photo_is_handed_out_for_a_year_without_a_session(): void
    {
        $photo = UploadedFile::fake()->image('a.webp')->store('products', 'public');

        $response = $this->get("/storage/{$photo}");

        $response->assertOk();
        $this->assertSame(Storage::disk('public')->path($photo), $response->baseResponse->getFile()->getPathname());
        $this->assertStringContainsString('max-age=31536000', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
        $response->assertCookieMissing(config('session.cookie'));
    }

    public function test_a_missing_photo_is_not_found(): void
    {
        $this->get('/storage/products/missing.webp')->assertNotFound();
    }

    public function test_files_that_are_not_photos_are_never_handed_out(): void
    {
        Storage::disk('public')->put('products/notes.txt', 'private notes');

        $this->get('/storage/products/notes.txt')->assertNotFound();
    }

    public function test_a_path_cannot_reach_outside_the_photos_folder(): void
    {
        // The fake private disk sits right beside the public one
        Storage::disk('local')->put('secret.webp', 'private');
        $this->assertFileExists(dirname(Storage::disk('public')->path('')).'/local/secret.webp');

        $this->get('/storage/..%2Flocal%2Fsecret.webp')->assertNotFound();
        $this->get('/storage/products/..%2F..%2Flocal%2Fsecret.webp')->assertNotFound();
    }
}
