<?php

declare(strict_types=1);

namespace Rebet\Tests\Application\Console\Command\Project;

use Rebet\Application\Console\Command\Project\ProjectInitCommand;
use Rebet\Tests\RebetConsoleTestCase;

class ProjectInitCommandTest extends RebetConsoleTestCase
{
    public const AVIRABLE_COMMANDS = [[ProjectInitCommand::class, __DIR__ . '/../../../../../../../../skeltons']];

    /**
     * A stub `composer.json` of a Composer project created via `composer create-project rebet/app-web`.
     *
     * @var string
     */
    public const COMPOSER_JSON = <<<JSON
        {
            "name": "rebet/app-web",
            "description": "Rebet web application skeleton",
            "type": "project",
            "require": {}
        }
        JSON;

    public function test_execute(): void
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
     * that already has a (stub) `composer.json` of `rebet/app-web` (since `project:init` now requires one), then
     * restore the original current directory afterward.
     *
     * @param  string   $sub_dir
     * @param  \Closure $callback function(string $work_dir) : mixed
     * @return mixed
     */
    protected function runInFreshWorkDir(string $sub_dir, \Closure $callback)
    {
        $work_dir = static::makeSubWorkingDir($sub_dir);
        file_put_contents("{$work_dir}/composer.json", static::COMPOSER_JSON);
        $cwd = getcwd();
        chdir($work_dir);
        try {
            return $callback($work_dir);
        } finally {
            chdir($cwd);
        }
    }

    public function test_execute_noInteraction(): void
    {
        $this->runInFreshWorkDir('project_init_no_interaction', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            $this->assertStringContainsString('locale => ' . locale_get_default() . ',', $display);
            $this->assertStringContainsString('timezone => ' . (date_default_timezone_get() ?: 'UTC') . ',', $display);
            $this->assertStringContainsString('domain => localhost,', $display);
            $this->assertStringContainsString('view => twig,', $display);

            $this->assertFileExists("{$work_dir}/app/core/.env");
            $this->assertFileExists("{$work_dir}/app/bin/assistant");
        });
    }

    public function test_execute_noInteraction_database(): void
    {
        $this->runInFreshWorkDir('project_init_no_interaction_database', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--database' => 'mysql'], ['interactive' => false]);
            $this->assertSame(0, $status);
            // Regression check: the option value must resolve by choice key ('mysql'), not only by
            // choice label ('MySQL'), see Command::viaOption().
            $this->assertStringContainsString('database => mysql,', $tester->getDisplay());
        });
    }

    public function test_execute_noInteraction_invalidDatabase(): void
    {
        // Regression check: Command::choice() silently falls back to null (instead of failing)
        // when the given option value does not resolve and the question is not interactive, so
        // ProjectInitCommand must reject it explicitly instead of proceeding with `database=null`.
        $this->runInFreshWorkDir('project_init_no_interaction_invalid_database', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--database' => 'oracle'], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                'Invalid value `oracle` given via `--database`. Choices are: `sqlite`, `mysql`, `mariadb`, `pgsql`.',
                $tester->getDisplay(),
            );
        });
    }

    public function test_execute_noInteraction_invalidCache(): void
    {
        // Same regression check as test_execute_noInteraction_invalidDatabase, but for --cache.
        $this->runInFreshWorkDir('project_init_no_interaction_invalid_cache', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--cache' => 'oracle'], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                'Invalid value `oracle` given via `--cache`. Choices are: `apcu`, `file`, `memcached`, `redis`.',
                $tester->getDisplay(),
            );
        });
    }

    public function test_execute_noInteraction_authWithoutDatabase_requiresOptions(): void
    {
        $this->runInFreshWorkDir('project_init_no_interaction_auth_missing', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--auth' => true], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                '`--auth` without a database requires `--auth-name`, `--auth-email` and `--auth-password` when running with `--no-interaction`.',
                $tester->getDisplay(),
            );
        });
    }

    public function test_execute_noInteraction_authWithoutDatabase(): void
    {
        $this->runInFreshWorkDir('project_init_no_interaction_auth', function (): void {
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

    public function test_execute_dryRun(): void
    {
        $this->runInFreshWorkDir('project_init_dry_run', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--dry-run' => true], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            $this->assertStringContainsString('nothing is written', $display);
            $this->assertStringContainsString("  - {$work_dir}/app/bin/assistant", $display);
            // Database is not used by default, so all `.devcontainer/docker/{driver}` dirs are excluded.
            $this->assertStringContainsString('62 files would be generated.', $display);
            $this->assertStringContainsString('Dry-run finished, nothing was written.', $display);

            // Nothing was actually written to disk.
            $this->assertFileDoesNotExist("{$work_dir}/app");
            $this->assertFileDoesNotExist("{$work_dir}/.devcontainer");
        });
    }

    public function test_execute_dryRun_excludesUnselectedDatabaseDirs(): void
    {
        $this->runInFreshWorkDir('project_init_dry_run_database', function (string $work_dir): void {
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

    public function test_execute_alreadyInitialized_anyTopLevelSkeltonEntry(): void
    {
        // Not just `app/`: any top-level skelton entry (eg. `tests/`, `.devcontainer/`)
        // already existing must also refuse to run.
        $this->runInFreshWorkDir('project_init_already_initialized', function (string $work_dir): void {
            mkdir("{$work_dir}/tests");

            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                "This directory seems to already be initialized (`{$work_dir}/tests` already exists).",
                $tester->getDisplay(),
            );

            // Nothing else was written.
            $this->assertFileDoesNotExist("{$work_dir}/app");
        });
    }

    public function test_execute_appDirWithOnlyVendorDir_isNotAlreadyInitialized(): void
    {
        // An `app/` directory that only has `vendor/` (eg. from a devcontainer running
        // `composer install` ahead of time) must not be treated as already initialized.
        $this->runInFreshWorkDir('project_init_app_vendor_only', function (string $work_dir): void {
            mkdir("{$work_dir}/app/vendor", 0o755, true);

            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--dry-run' => true], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertStringContainsString('would be generated.', $tester->getDisplay());
        });
    }

    public function test_execute_appDirWithMoreThanVendorDir_isAlreadyInitialized(): void
    {
        // But if `app/` has anything else besides `vendor/`, it is still considered initialized.
        $this->runInFreshWorkDir('project_init_app_vendor_and_more', function (string $work_dir): void {
            mkdir("{$work_dir}/app/vendor", 0o755, true);
            touch("{$work_dir}/app/other-file.txt");

            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--dry-run' => true], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                "This directory seems to already be initialized (`{$work_dir}/app` already exists).",
                $tester->getDisplay(),
            );
        });
    }

    public function test_execute_noComposerJson_refusesToRun(): void
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
                $tester->getDisplay(),
            );
        } finally {
            chdir($cwd);
        }
    }

    public function test_execute_composerRequire_default(): void
    {
        // RebetTestCase enables System::testing() for every test, so ProjectInitCommand::
        // composerRequire() never actually shells out to Composer here; it only prints the
        // command it would have run.
        $this->runInFreshWorkDir('project_init_composer_require_default', function (): void {
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
                $display,
            );
        });
    }

    public function test_execute_composerRequire_viewBlade(): void
    {
        $this->runInFreshWorkDir('project_init_composer_require_blade', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--view' => 'blade'], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            $this->assertStringContainsString('composer require illuminate/view', $display);
            $this->assertStringNotContainsString('twig/twig', $display);
        });
    }

    public function test_execute_composerRequire_cacheRedis(): void
    {
        $this->runInFreshWorkDir('project_init_composer_require_redis', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--cache' => 'redis'], ['interactive' => false]);
            $this->assertSame(0, $status);
            // 'predis/predis' (cache=redis) and 'twig/twig' (default view) are both required
            // together, in COMPOSER_REQUIRE's declared group order (cache before view).
            $this->assertStringContainsString('composer require predis/predis twig/twig', $tester->getDisplay());
        });
    }

    public function test_execute_noInteraction_session_defaultsToNative(): void
    {
        $this->runInFreshWorkDir('project_init_session_default', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertMatchesRegularExpression('/session => native,?$/m', $tester->getDisplay());
        });
    }

    public function test_execute_noInteraction_session_redis(): void
    {
        $this->runInFreshWorkDir('project_init_session_redis', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--session' => 'redis'], ['interactive' => false]);
            $this->assertSame(0, $status);
            $display = $tester->getDisplay();
            $this->assertMatchesRegularExpression('/session => redis,?$/m', $display);
            // Regression check for COMPOSER_REQUIRE['session']['redis'].
            $this->assertStringContainsString('composer require predis/predis twig/twig', $display);
        });
    }

    public function test_execute_noInteraction_session_mongodb(): void
    {
        $this->runInFreshWorkDir('project_init_session_mongodb', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--session' => 'mongodb'], ['interactive' => false]);
            $this->assertSame(0, $status);
            $display = $tester->getDisplay();
            $this->assertMatchesRegularExpression('/session => mongodb,?$/m', $display);
            // Regression check for COMPOSER_REQUIRE['session']['mongodb'].
            $this->assertStringContainsString('composer require mongodb/mongodb twig/twig', $display);
        });
    }

    public function test_execute_noInteraction_session_databaseRequiresDatabase(): void
    {
        // 'database' is only a valid --session choice when a database is actually used.
        $this->runInFreshWorkDir('project_init_session_database_without_db', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--session' => 'database'], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                'Invalid value `database` given via `--session`. Choices are: `native`, `memcached`, `redis`, `mongodb`.',
                $tester->getDisplay(),
            );
        });
    }

    public function test_execute_noInteraction_session_databaseWithDatabase(): void
    {
        $this->runInFreshWorkDir('project_init_session_database_with_db', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--session' => 'database', '--database' => 'mysql'], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertMatchesRegularExpression('/session => database,?$/m', $tester->getDisplay());
        });
    }

    public function test_execute_noInteraction_invalidSession(): void
    {
        $this->runInFreshWorkDir('project_init_invalid_session', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--session' => 'oracle'], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString(
                'Invalid value `oracle` given via `--session`. Choices are: `native`, `memcached`, `redis`, `mongodb`.',
                $tester->getDisplay(),
            );
        });
    }

    /**
     * Answers for a full interactive run that accepts every default (no database, no auth, no
     * cache, twig, native session), followed by the given answers for the settings review prompt.
     *
     * @param  string[] $review_answers
     * @return string[]
     */
    protected function minimalInteractiveInputs(array $review_answers): array
    {
        return array_merge(
            ['', '', '', '', '', 'n', 'n', '', 'n', ''], // defaults through session storage
            $review_answers,
        );
    }

    public function test_execute_interactive_reviewConfirmYes(): void
    {
        $this->runInFreshWorkDir('project_init_review_yes', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $tester->setInputs($this->minimalInteractiveInputs([
                '', // Are these settings OK? -> yes (default)
                'y', // Are you really sure? -> yes
            ]));
            $status  = $tester->execute([], ['interactive' => true]);
            $display = $tester->getDisplay();
            $this->assertSame(0, $status);
            $this->assertStringContainsString('Current settings', $display);
            $this->assertStringContainsString('Are these settings OK?', $display);
            $this->assertStringContainsString('Are you really sure these settings are correct and ready to proceed?', $display);
            $this->assertStringContainsString('Generating application files from skeltons...', $display);
            $this->assertStringNotContainsString('Aborted', $display);
        });
    }

    public function test_execute_interactive_reviewRedoStep(): void
    {
        $this->runInFreshWorkDir('project_init_review_redo', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $tester->setInputs($this->minimalInteractiveInputs([
                '5',     // Are these settings OK? -> type the step number to fix -> 5) View
                'blade', // View Engine -> blade
                'yes',   // Are these settings OK (redisplayed)? -> yes
                'y',     // Are you really sure? -> yes
            ]));
            $status  = $tester->execute([], ['interactive' => true]);
            $display = $tester->getDisplay();
            $this->assertSame(0, $status);
            // The redone step is asked a second time, and the final settings/generation reflect it.
            $this->assertSame(2, substr_count($display, '5) Setup View Configs (5/7)'));
            $this->assertStringContainsString('composer require illuminate/view', $display);
        });
    }

    public function test_execute_interactive_reviewAbort(): void
    {
        $this->runInFreshWorkDir('project_init_review_abort', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $tester->setInputs($this->minimalInteractiveInputs([
                'abort', // Are these settings OK? -> abort
                'y',     // Are you sure you want to abort?
            ]));
            $status  = $tester->execute([], ['interactive' => true]);
            $display = $tester->getDisplay();
            $this->assertSame(1, $status);
            $this->assertStringContainsString('Are you sure you want to abort?', $display);
            $this->assertStringContainsString('Aborted by user, nothing was done.', $display);
            $this->assertStringNotContainsString('Generating application files from skeltons...', $display);
        });
    }

    public function test_execute_interactive_reviewAbort_declinedKeepsReviewing(): void
    {
        $this->runInFreshWorkDir('project_init_review_abort_declined', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $tester->setInputs($this->minimalInteractiveInputs([
                'abort', // Are these settings OK? -> abort
                'n',     // Are you sure you want to abort? -> no, keep reviewing
                'yes',   // Are these settings OK (redisplayed)? -> yes
                'y',     // Are you really sure? -> yes
            ]));
            $status  = $tester->execute([], ['interactive' => true]);
            $display = $tester->getDisplay();
            $this->assertSame(0, $status);
            $this->assertStringContainsString('Generating application files from skeltons...', $display);
            $this->assertStringNotContainsString('Aborted', $display);
        });
    }

    public function test_execute_gitignoreExists_isOverwritten(): void
    {
        // A `.gitignore` (that a project created via `composer create-project` usually already has)
        // must not be treated as already initialized, and is overwritten by the skelton.
        $this->runInFreshWorkDir('project_init_gitignore_exists', function (string $work_dir): void {
            file_put_contents("{$work_dir}/.gitignore", "/old-entry/\n");

            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertStringNotContainsString('already be initialized', $tester->getDisplay());
            $this->assertFileEquals(__DIR__ . '/../../../../../../../../skeltons/.lp.gitignore', "{$work_dir}/.gitignore");
        });
    }

    public function test_execute_composerNameIsNotRebetAppWeb(): void
    {
        $this->runInFreshWorkDir('project_init_composer_name_invalid', function (string $work_dir): void {
            file_put_contents("{$work_dir}/composer.json", '{"name": "acme/other-project"}');

            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString("The name in `{$work_dir}/composer.json` must be `rebet/app-web`, but `acme/other-project` given.", $tester->getDisplay());
            $this->assertFileDoesNotExist("{$work_dir}/app");
        });
    }

    public function test_execute_composerNameStartsWithRebetAppWebButNotExactlyMatched(): void
    {
        $this->runInFreshWorkDir('project_init_composer_name_not_exact', function (string $work_dir): void {
            file_put_contents("{$work_dir}/composer.json", '{"name": "rebet/app-web-extra"}');

            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString("The name in `{$work_dir}/composer.json` must be `rebet/app-web`, but `rebet/app-web-extra` given.", $tester->getDisplay());
            $this->assertFileDoesNotExist("{$work_dir}/app");
        });
    }

    public function test_execute_composerNameIsMissing(): void
    {
        $this->runInFreshWorkDir('project_init_composer_name_missing', function (string $work_dir): void {
            file_put_contents("{$work_dir}/composer.json", '{}');

            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString("The name in `{$work_dir}/composer.json` must be `rebet/app-web`, but nothing given.", $tester->getDisplay());
            $this->assertFileDoesNotExist("{$work_dir}/app");
        });
    }

    public function test_execute_updatesComposerNameAndDescription(): void
    {
        $this->runInFreshWorkDir('project_init_composer_update', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            // Give the vendor explicitly to check that the given vendor (not the default) is used.
            $status = $tester->execute(['--vendor' => 'acme'], ['interactive' => false]);
            $this->assertSame(0, $status);

            $composer_json = file_get_contents("{$work_dir}/composer.json");
            $json          = json_decode($composer_json, true);
            $this->assertSame('acme/project-init-composer-update', $json['name']);
            $this->assertSame('project-init-composer-update web application', $json['description']);
            // The other properties are kept as they are (an empty object stays `{}`).
            $this->assertSame(['name', 'description', 'type', 'require'], array_keys($json));
            $this->assertSame('project', $json['type']);
            $this->assertStringContainsString('"require": {}', $composer_json);
        });
    }

    public function test_execute_dryRun_doesNotUpdateComposerJson(): void
    {
        $this->runInFreshWorkDir('project_init_composer_dry_run', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--dry-run' => true], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            $this->assertStringContainsString('composer.json that would be updated...', $display);
            $this->assertStringContainsString('  - name        : project-init-composer-dry-run/project-init-composer-dry-run', $display);
            $this->assertStringContainsString('  - description : project-init-composer-dry-run web application', $display);
            $this->assertSame(static::COMPOSER_JSON, file_get_contents("{$work_dir}/composer.json"));
        });
    }

    public function test_execute_noInteraction_vendorDefaultsToCodeName(): void
    {
        $this->runInFreshWorkDir('project_init_vendor_default', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertStringContainsString('vendor => project-init-vendor-default,', $tester->getDisplay());
            $this->assertSame('project-init-vendor-default/project-init-vendor-default', json_decode(file_get_contents("{$work_dir}/composer.json"), true)['name']);
        });
    }

    public function test_execute_invalidVendorViaOption(): void
    {
        $this->runInFreshWorkDir('project_init_vendor_invalid', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--vendor' => 'Invalid Vendor'], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString('`Invalid Vendor` is invalid as a Composer vendor name', $tester->getDisplay());
            $this->assertFileDoesNotExist("{$work_dir}/app");
        });
    }

    public function test_execute_interactive_invalidVendorIsAskedAgain(): void
    {
        $this->runInFreshWorkDir('project_init_vendor_ask_again', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $tester->setInputs(array_merge(
                ['', 'Invalid Vendor'], // Code name -> default, Vendor -> invalid, so it is asked again
                array_slice($this->minimalInteractiveInputs(['', 'y']), 1),
            ));
            $status  = $tester->execute([], ['interactive' => true]);
            $display = $tester->getDisplay();
            $this->assertSame(0, $status);
            $this->assertStringContainsString('`Invalid Vendor` is invalid as a Composer vendor name', $display);
            $this->assertSame('project-init-vendor-ask-again/project-init-vendor-ask-again', json_decode(file_get_contents("{$work_dir}/composer.json"), true)['name']);
        });
    }
}
