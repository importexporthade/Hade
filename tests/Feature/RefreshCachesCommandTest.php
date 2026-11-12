<?php

namespace Tests\Feature;

use App\Support\AppCaches;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RefreshCachesCommandTest extends TestCase
{
    private const string FIRST_COMMIT = '1111111111111111111111111111111111111111';

    private const string SECOND_COMMIT = '2222222222222222222222222222222222222222';

    private string $site;

    /** @var AppCaches&MockInterface */
    private AppCaches $caches;

    private bool $cachesOnDisk = false;

    private int $builds = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // A deployed copy of the site, with git's files and an .env
        $this->site = storage_path('framework/testing/refresh-caches-'.uniqid());
        File::ensureDirectoryExists("{$this->site}/.git/refs/heads");
        File::put("{$this->site}/.git/HEAD", "ref: refs/heads/main\n");
        $this->deploy(self::FIRST_COMMIT);
        File::put("{$this->site}/.env", 'APP_NAME=Hade');

        $this->caches = Mockery::mock(AppCaches::class, [$this->site, "{$this->site}/cached-version.txt"])->makePartial();
        $this->caches->allows('exist')->andReturnUsing(fn (): bool => $this->cachesOnDisk);
        $this->caches->allows('build')->andReturnUsing(function (): void {
            $this->builds++;
            $this->cachesOnDisk = true;
        });
        $this->app->instance(AppCaches::class, $this->caches);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->site);

        parent::tearDown();
    }

    public function test_the_caches_are_built_once_and_left_alone_while_nothing_changes(): void
    {
        $this->artisan('hade:refresh-caches')->expectsOutputToContain(self::FIRST_COMMIT)->assertSuccessful();
        $this->artisan('hade:refresh-caches')->doesntExpectOutputToContain('Cachés rehechas')->assertSuccessful();

        $this->assertSame(1, $this->builds);
    }

    public function test_a_deploy_with_a_new_commit_rebuilds_them(): void
    {
        $this->artisan('hade:refresh-caches');

        $this->deploy(self::SECOND_COMMIT);
        $this->artisan('hade:refresh-caches')->expectsOutputToContain(self::SECOND_COMMIT);

        $this->assertSame(2, $this->builds);
    }

    public function test_saving_the_env_rebuilds_them(): void
    {
        $this->artisan('hade:refresh-caches');

        touch("{$this->site}/.env", time() + 60);
        clearstatcache();
        $this->artisan('hade:refresh-caches');

        $this->assertSame(2, $this->builds);
    }

    public function test_caches_cleared_by_the_cleanup_are_built_again(): void
    {
        $this->artisan('hade:refresh-caches');

        $this->cachesOnDisk = false;
        $this->artisan('hade:refresh-caches');

        $this->assertSame(2, $this->builds);
    }

    public function test_the_commit_is_read_however_git_stored_it(): void
    {
        $this->assertSame(self::FIRST_COMMIT, $this->caches->deployedCommit());

        File::delete("{$this->site}/.git/refs/heads/main");
        File::put("{$this->site}/.git/packed-refs", "# pack-refs with: peeled\n".self::SECOND_COMMIT." refs/heads/main\n");
        $this->assertSame(self::SECOND_COMMIT, $this->caches->deployedCommit());

        File::put("{$this->site}/.git/HEAD", self::FIRST_COMMIT."\n");
        $this->assertSame(self::FIRST_COMMIT, $this->caches->deployedCommit());

        File::deleteDirectory("{$this->site}/.git");
        $this->assertNull($this->caches->deployedCommit());
    }

    public function test_the_scheduler_refreshes_the_caches_inside_its_own_process(): void
    {
        // A task started as a separate process would not see this test's fake caches
        $this->artisan('schedule:run')->assertSuccessful();

        $this->assertSame(1, $this->builds);
    }

    public function test_no_scheduled_task_needs_a_separate_process(): void
    {
        $this->assertContainsOnlyInstancesOf(CallbackEvent::class, app(Schedule::class)->events());
        $this->assertNotEmpty(app(Schedule::class)->events());
    }

    private function deploy(string $commit): void
    {
        File::put("{$this->site}/.git/refs/heads/main", "{$commit}\n");
    }
}
