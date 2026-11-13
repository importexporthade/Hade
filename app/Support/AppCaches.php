<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Laravel's caches of config, routes, events and views, kept in step with the code on a host
 * without SSH: they are rebuilt when a deploy brings a new commit, when .env is edited, or
 * when something cleared them, and left alone otherwise.
 */
class AppCaches
{
    /**
     * @param  string|null  $codePath  Folder whose .git tells the deployed commit, and whose .env is watched.
     * @param  string|null  $versionFile  Where the version the caches were built for is remembered.
     */
    public function __construct(
        private ?string $codePath = null,
        private ?string $versionFile = null,
    ) {
        $this->codePath ??= base_path();
        $this->versionFile ??= storage_path('framework/cached-version.txt');
    }

    public function areUpToDate(): bool
    {
        return $this->exist()
            && File::exists($this->versionFile)
            && File::get($this->versionFile) === $this->currentVersion();
    }

    public function rebuild(): void
    {
        $this->build();

        File::put($this->versionFile, $this->currentVersion());
    }

    /**
     * The deployed commit and when .env was last saved; either changing means the cached
     * routes or config may be stale.
     */
    public function currentVersion(): string
    {
        $env = "{$this->codePath}/.env";

        return ($this->deployedCommit() ?? 'sin-git').'|env:'.(File::exists($env) ? File::lastModified($env) : 'ninguno');
    }

    /**
     * The commit the deploy checked out, read from .git's files since the host cannot run git.
     */
    public function deployedCommit(): ?string
    {
        $git = "{$this->codePath}/.git";

        if (! File::isFile("{$git}/HEAD")) {
            return null;
        }

        $head = trim(File::get("{$git}/HEAD"));

        if (! str_starts_with($head, 'ref: ')) {
            return $head;
        }

        $ref = substr($head, 5);

        if (File::isFile("{$git}/{$ref}")) {
            return trim(File::get("{$git}/{$ref}"));
        }

        $packedRefs = File::isFile("{$git}/packed-refs") ? File::get("{$git}/packed-refs") : '';

        return preg_match('/^([0-9a-f]{40}) '.preg_quote($ref, '/').'$/m', $packedRefs, $match) ? $match[1] : null;
    }

    /**
     * Whether the config and route caches are on disk; "clean-server --borrar" clears them.
     */
    public function exist(): bool
    {
        return app()->configurationIsCached() && app()->routesAreCached();
    }

    /**
     * Caches config, events, routes, views and Filament's components, replacing old ones.
     */
    public function build(): void
    {
        Artisan::call('optimize');
    }

    /**
     * Compiles the Blade views again, after a deploy that changed them.
     */
    public function rebuildViews(): void
    {
        Artisan::call('view:clear');
        Artisan::call('view:cache');
    }

    /**
     * Caches the routes again, after a deploy that changed them.
     */
    public function rebuildRoutes(): void
    {
        Artisan::call('route:clear');
        Artisan::call('route:cache');
    }

    /**
     * Reads .env again: empties the application cache and caches the config anew.
     */
    public function rebuildConfig(): void
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('config:cache');
    }
}
