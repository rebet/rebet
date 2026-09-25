<?php
namespace Rebet\Tests\Application\Console\Command\Project;

use Rebet\Application\Console\Command\Project\ProjectInitCommand;
use Rebet\Tests\RebetConsoleTestCase;

class ProjectInitCommandTest extends RebetConsoleTestCase
{
    const AVIRABLE_COMMANDS = [[ProjectInitCommand::class, __DIR__.'/../../../../../../../../skeltons']];

    public function test_execute()
    {
        // $this->execute('init');
        $this->assertTrue(true);

        // @todo implements
        // $tester = $this->getCommandTester(ProjectInitCommand::NAME);
        // $status = $tester->execute([]);
        // $this->assertSame(0, $status);
        // $this->assertSame("Application ready! Build something amazing.".PHP_EOL, $tester->getDisplay());
    }

    /**
     * Run the given callback with the current directory changed to a fresh sub working directory
     * that already has a (stub) `composer.json` (since `project:init` now requires one), then
     * restore the original current directory afterward.
     *
     * @param string $sub_dir
     * @param \Closure $callback function(string $work_dir) : mixed
     * @return mixed
     */
    protected function runInFreshWorkDir(string $sub_dir, \Closure $callback)
    {
        $work_dir = static::makeSubWorkingDir($sub_dir);
        file_put_contents("{$work_dir}/composer.json", '{}');
        $cwd = getcwd();
        chdir($work_dir);
        try {
            return $callback($work_dir);
        } finally {
            chdir($cwd);
        }
    }

    public function test_execute_noInteraction()
    {
        $this->runInFreshWorkDir('project_init_no_interaction', function (string $work_dir) {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            $this->assertStringContainsString('locale => '.locale_get_default().',', $display);
            $this->assertStringContainsString('timezone => '.(date_default_timezone_get() ?: 'UTC').',', $display);
            $this->assertStringContainsString('domain => localhost,', $display);
            $this->assertStringContainsString('view => twig,', $display);

            $this->assertFileExists("{$work_dir}/app/core/.env");
            $this->assertFileExists("{$work_dir}/bin/app");
        });
    }

    public function test_execute_noInteraction_database()
    {
        $this->runInFreshWorkDir('project_init_no_interaction_database', function () {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--database' => 'mysql'], ['interactive' => false]);
            $this->assertSame(0, $status);
            // Regression check: the option value must resolve by choice key ('mysql'), not only by
            // choice label ('MySQL'), see Command::viaOption().
            $this->assertStringContainsString('database => mysql,', $tester->getDisplay());
        });
    }

    public function test_execute_noInteraction_invalidDatabase()
    {
        // Regression check: Command::choice() silently falls back to null (instead of failing)
        // when the given option value does not resolve and the question is not interactive, so
        // ProjectInitCommand must reject it explicitly instead of proceeding with `database=null`.
        $this->runInFreshWorkDir('project_init_no_interaction_invalid_database', function () {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--database' => 'oracle'], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                'Invalid value `oracle` given via `--database`. Choices are: `sqlite`, `mysql`, `mariadb`, `pgsql`.',
                $tester->getDisplay()
            );
        });
    }

    public function test_execute_noInteraction_invalidCache()
    {
        // Same regression check as test_execute_noInteraction_invalidDatabase, but for --cache.
        $this->runInFreshWorkDir('project_init_no_interaction_invalid_cache', function () {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--cache' => 'oracle'], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                'Invalid value `oracle` given via `--cache`. Choices are: `apcu`, `file`, `memcached`, `redis`.',
                $tester->getDisplay()
            );
        });
    }

    public function test_execute_noInteraction_authWithoutDatabase_requiresOptions()
    {
        $this->runInFreshWorkDir('project_init_no_interaction_auth_missing', function () {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--auth' => true], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                '`--auth` without a database requires `--auth-name`, `--auth-email` and `--auth-password` when running with `--no-interaction`.',
                $tester->getDisplay()
            );
        });
    }

    public function test_execute_noInteraction_authWithoutDatabase()
    {
        $this->runInFreshWorkDir('project_init_no_interaction_auth', function () {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([
                '--auth'          => true,
                '--auth-name'     => 'Admin',
                '--auth-email'    => 'admin@example.com',
                '--auth-password' => 'P@ssw0rd1',
            ], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertStringContainsString('auth_name => Admin,', $tester->getDisplay());
            $this->assertStringContainsString('auth_email => admin@example.com,', $tester->getDisplay());
        });
    }

    public function test_execute_dryRun()
    {
        $this->runInFreshWorkDir('project_init_dry_run', function (string $work_dir) {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--dry-run' => true], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            $this->assertStringContainsString('nothing is written', $display);
            $this->assertStringContainsString("  - {$work_dir}/bin/app", $display);
            // Database is not used by default, so all `.devcontainer/docker/{driver}` dirs are excluded.
            $this->assertStringContainsString('45 files would be generated.', $display);
            $this->assertStringContainsString('Dry-run finished, nothing was written.', $display);

            // Nothing was actually written to disk.
            $this->assertFileDoesNotExist("{$work_dir}/app");
            $this->assertFileDoesNotExist("{$work_dir}/bin");
        });
    }

    public function test_execute_dryRun_excludesUnselectedDatabaseDirs()
    {
        $this->runInFreshWorkDir('project_init_dry_run_database', function (string $work_dir) {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--dry-run' => true, '--database' => 'mysql'], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            $this->assertStringContainsString("{$work_dir}/.devcontainer/docker/mysql/", $display);
            $this->assertStringNotContainsString("{$work_dir}/.devcontainer/docker/mariadb/", $display);
            $this->assertStringNotContainsString("{$work_dir}/.devcontainer/docker/pgsql/", $display);
            $this->assertStringNotContainsString("{$work_dir}/.devcontainer/docker/sqlite/", $display);
        });
    }

    public function test_execute_alreadyInitialized_anyTopLevelSkeltonEntry()
    {
        // Not just `app/`: any top-level skelton entry (eg. `bin/`, `.devcontainer/`, `tests/`)
        // already existing must also refuse to run.
        $this->runInFreshWorkDir('project_init_already_initialized', function (string $work_dir) {
            mkdir("{$work_dir}/bin");

            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                "This directory seems to already be initialized (`{$work_dir}/bin` already exists).",
                $tester->getDisplay()
            );

            // Nothing else was written.
            $this->assertFileDoesNotExist("{$work_dir}/app");
        });
    }

    public function test_execute_appDirWithOnlyVendorDir_isNotAlreadyInitialized()
    {
        // An `app/` directory that only has `vendor/` (eg. from a devcontainer running
        // `composer install` ahead of time) must not be treated as already initialized.
        $this->runInFreshWorkDir('project_init_app_vendor_only', function (string $work_dir) {
            mkdir("{$work_dir}/app/vendor", 0755, true);

            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--dry-run' => true], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertStringContainsString('would be generated.', $tester->getDisplay());
        });
    }

    public function test_execute_appDirWithMoreThanVendorDir_isAlreadyInitialized()
    {
        // But if `app/` has anything else besides `vendor/`, it is still considered initialized.
        $this->runInFreshWorkDir('project_init_app_vendor_and_more', function (string $work_dir) {
            mkdir("{$work_dir}/app/vendor", 0755, true);
            touch("{$work_dir}/app/other-file.txt");

            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--dry-run' => true], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                "This directory seems to already be initialized (`{$work_dir}/app` already exists).",
                $tester->getDisplay()
            );
        });
    }

    public function test_execute_noComposerJson_refusesToRun()
    {
        // Unlike runInFreshWorkDir(), this deliberately does NOT create a composer.json.
        $work_dir = static::makeSubWorkingDir('project_init_no_composer_json');
        $cwd      = getcwd();
        chdir($work_dir);
        try {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--dry-run' => true], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                "This directory does not seem to be a Composer project (`{$work_dir}/composer.json` not found).",
                $tester->getDisplay()
            );
        } finally {
            chdir($cwd);
        }
    }

    public function test_execute_composerRequire_default()
    {
        // RebetTestCase enables System::testing() for every test, so ProjectInitCommand::
        // composerRequire() never actually shells out to Composer here; it only prints the
        // command it would have run.
        $this->runInFreshWorkDir('project_init_composer_require_default', function () {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            // Default view is 'twig', so only twig/twig is required (session/cache have no match).
            $this->assertStringContainsString('composer require twig/twig', $display);
            $this->assertStringNotContainsString('mongodb/mongodb', $display);
            $this->assertStringNotContainsString('predis/predis', $display);
            // COMPOSER_REQUIRE_DEV's 'always' group is applied unconditionally.
            $this->assertStringContainsString(
                'composer require --dev friendsofphp/php-cs-fixer phpstan/phpstan phpunit/phpunit psy/psysh',
                $display
            );
        });
    }

    public function test_execute_composerRequire_viewBlade()
    {
        $this->runInFreshWorkDir('project_init_composer_require_blade', function () {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--view' => 'blade'], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            $this->assertStringContainsString('composer require illuminate/view', $display);
            $this->assertStringNotContainsString('twig/twig', $display);
        });
    }

    public function test_execute_composerRequire_cacheRedis()
    {
        $this->runInFreshWorkDir('project_init_composer_require_redis', function () {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--cache' => 'redis'], ['interactive' => false]);
            $this->assertSame(0, $status);
            // 'predis/predis' (cache=redis) and 'twig/twig' (default view) are both required
            // together, in COMPOSER_REQUIRE's declared group order (cache before view).
            $this->assertStringContainsString('composer require predis/predis twig/twig', $tester->getDisplay());
        });
    }

    public function test_execute_noInteraction_session_defaultsToNative()
    {
        $this->runInFreshWorkDir('project_init_session_default', function () {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertStringContainsString('session => native,', $tester->getDisplay());
        });
    }

    public function test_execute_noInteraction_session_redis()
    {
        $this->runInFreshWorkDir('project_init_session_redis', function () {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--session' => 'redis'], ['interactive' => false]);
            $this->assertSame(0, $status);
            $display = $tester->getDisplay();
            $this->assertStringContainsString('session => redis,', $display);
            // Regression check for COMPOSER_REQUIRE['session']['redis'].
            $this->assertStringContainsString('composer require predis/predis twig/twig', $display);
        });
    }

    public function test_execute_noInteraction_session_mongodb()
    {
        $this->runInFreshWorkDir('project_init_session_mongodb', function () {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--session' => 'mongodb'], ['interactive' => false]);
            $this->assertSame(0, $status);
            $display = $tester->getDisplay();
            $this->assertStringContainsString('session => mongodb,', $display);
            // Regression check for COMPOSER_REQUIRE['session']['mongodb'].
            $this->assertStringContainsString('composer require mongodb/mongodb twig/twig', $display);
        });
    }

    public function test_execute_noInteraction_session_databaseRequiresDatabase()
    {
        // 'database' is only a valid --session choice when a database is actually used.
        $this->runInFreshWorkDir('project_init_session_database_without_db', function () {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--session' => 'database'], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                'Invalid value `database` given via `--session`. Choices are: `native`, `memcached`, `redis`, `mongodb`.',
                $tester->getDisplay()
            );
        });
    }

    public function test_execute_noInteraction_session_databaseWithDatabase()
    {
        $this->runInFreshWorkDir('project_init_session_database_with_db', function () {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--session' => 'database', '--database' => 'mysql'], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertStringContainsString('session => database,', $tester->getDisplay());
        });
    }

    public function test_execute_noInteraction_invalidSession()
    {
        $this->runInFreshWorkDir('project_init_invalid_session', function () {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--session' => 'oracle'], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                'Invalid value `oracle` given via `--session`. Choices are: `native`, `memcached`, `redis`, `mongodb`.',
                $tester->getDisplay()
            );
        });
    }
}
