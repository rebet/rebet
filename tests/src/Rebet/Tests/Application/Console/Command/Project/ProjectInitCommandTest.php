<?php

declare(strict_types=1);

namespace Rebet\Tests\Application\Console\Command\Project;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebet\Application\Console\Command\Project\ProjectInitCommand;
use Rebet\Auth\Password;
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
        $this->runInFreshWorkDir('project-init-no-interaction', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            $this->assertStringContainsString('locale => ' . locale_get_default() . ',', $display);
            $this->assertStringContainsString('timezone => ' . (date_default_timezone_get() ?: 'UTC') . ',', $display);
            $this->assertStringContainsString('domain => project-init-no-interaction.localhost,', $display);
            $this->assertStringContainsString('view => twig,', $display);

            $this->assertFileExists("{$work_dir}/.env");
            $this->assertFileExists("{$work_dir}/bin/assistant");
        });
    }

    public function test_execute_noInteraction_database(): void
    {
        $this->runInFreshWorkDir('project-init-no-interaction-database', function (): void {
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
        $this->runInFreshWorkDir('project-init-no-interaction-invalid-database', function (): void {
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
        $this->runInFreshWorkDir('project-init-no-interaction-invalid-cache', function (): void {
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
        $this->runInFreshWorkDir('project-init-no-interaction-auth-missing', function (): void {
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
        $this->runInFreshWorkDir('project-init-no-interaction-auth', function (): void {
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
        $this->runInFreshWorkDir('project-init-dry-run', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--dry-run' => true], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            $this->assertStringContainsString('nothing is written', $display);
            $this->assertStringContainsString("  - {$work_dir}/bin/assistant", $display);
            // Database is not used by default, so all `.devcontainer/docker/{driver}` dirs are excluded.
            $this->assertStringContainsString('63 files would be generated.', $display);
            $this->assertStringContainsString('Dry-run finished, nothing was written.', $display);

            // Nothing was actually written to disk.
            $this->assertFileDoesNotExist("{$work_dir}/app");
            $this->assertFileDoesNotExist("{$work_dir}/.devcontainer");
        });
    }

    public function test_execute_dryRun_excludesUnselectedDatabaseDirs(): void
    {
        $this->runInFreshWorkDir('project-init-dry-run-database', function (string $work_dir): void {
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
        $this->runInFreshWorkDir('project-init-already-initialized', function (string $work_dir): void {
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

    public function test_execute_noComposerJson_refusesToRun(): void
    {
        // Unlike runInFreshWorkDir(), this deliberately does NOT create a composer.json.
        $work_dir = static::makeSubWorkingDir('project-init-no-composer-json');
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
        $this->runInFreshWorkDir('project-init-composer-require-default', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            // Default view is 'twig', so only twig/twig is required (session/cache have no match).
            $this->assertStringContainsString('composer require twig/twig:^3.21', $display);
            $this->assertStringNotContainsString('mongodb/mongodb', $display);
            $this->assertStringNotContainsString('predis/predis', $display);
            // COMPOSER_REQUIRE_DEV's 'always' group is applied unconditionally.
            $this->assertStringContainsString(
                'composer require --dev friendsofphp/php-cs-fixer:^3.95 phpstan/phpstan:^2.2 phpunit/phpunit:^11.5 psy/psysh:^0.12.24',
                $display,
            );
            // Host's missing PHP extensions (ex. ext-dom for phpunit) must not block the installation,
            // but the PHP version is still checked.
            $this->assertStringContainsString("--no-interaction --ignore-platform-req='ext-*' ", $display);
            $this->assertStringNotContainsString('--ignore-platform-reqs', $display);
        });
    }

    public function test_execute_composerRequire_viewBlade(): void
    {
        $this->runInFreshWorkDir('project-init-composer-require-blade', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--view' => 'blade'], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            $this->assertStringContainsString('composer require illuminate/view:^13.21', $display);
            $this->assertStringNotContainsString('twig/twig', $display);
        });
    }

    public function test_execute_composerRequire_cacheRedis(): void
    {
        $this->runInFreshWorkDir('project-init-composer-require-redis', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--cache' => 'redis'], ['interactive' => false]);
            $this->assertSame(0, $status);
            // 'predis/predis' (cache=redis) and 'twig/twig' (default view) are both required
            // together, in COMPOSER_REQUIRE's declared group order (cache before view).
            $this->assertStringContainsString('composer require predis/predis:^2.3 twig/twig:^3.21', $tester->getDisplay());
        });
    }

    public function test_execute_noInteraction_session_defaultsToNative(): void
    {
        $this->runInFreshWorkDir('project-init-session-default', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertMatchesRegularExpression('/session => native,?$/m', $tester->getDisplay());
        });
    }

    public function test_execute_noInteraction_session_redis(): void
    {
        $this->runInFreshWorkDir('project-init-session-redis', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--session' => 'redis'], ['interactive' => false]);
            $this->assertSame(0, $status);
            $display = $tester->getDisplay();
            $this->assertMatchesRegularExpression('/session => redis,?$/m', $display);
            // Regression check for COMPOSER_REQUIRE['session']['redis'].
            $this->assertStringContainsString('composer require predis/predis:^2.3 twig/twig:^3.21', $display);
        });
    }

    public function test_execute_noInteraction_session_mongodb(): void
    {
        $this->runInFreshWorkDir('project-init-session-mongodb', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--session' => 'mongodb'], ['interactive' => false]);
            $this->assertSame(0, $status);
            $display = $tester->getDisplay();
            $this->assertMatchesRegularExpression('/session => mongodb,?$/m', $display);
            // Regression check for COMPOSER_REQUIRE['session']['mongodb'].
            $this->assertStringContainsString('composer require mongodb/mongodb:^2.3 twig/twig:^3.21', $display);
        });
    }

    public function test_execute_noInteraction_session_databaseRequiresDatabase(): void
    {
        // 'database' is only a valid --session choice when a database is actually used.
        $this->runInFreshWorkDir('project-init-session-database-without-db', function (): void {
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
        $this->runInFreshWorkDir('project-init-session-database-with-db', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--session' => 'database', '--database' => 'mysql'], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertMatchesRegularExpression('/session => database,?$/m', $tester->getDisplay());
        });
    }

    public function test_execute_noInteraction_invalidSession(): void
    {
        $this->runInFreshWorkDir('project-init-invalid-session', function (): void {
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
        $this->runInFreshWorkDir('project-init-review-yes', function (): void {
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
        $this->runInFreshWorkDir('project-init-review-redo', function (): void {
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
            $this->assertStringContainsString('composer require illuminate/view:^13.21', $display);
        });
    }

    public function test_execute_interactive_reviewAbort(): void
    {
        $this->runInFreshWorkDir('project-init-review-abort', function (): void {
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
        $this->runInFreshWorkDir('project-init-review-abort-declined', function (): void {
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
        $this->runInFreshWorkDir('project-init-gitignore-exists', function (string $work_dir): void {
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
        $this->runInFreshWorkDir('project-init-composer-name-invalid', function (string $work_dir): void {
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
        $this->runInFreshWorkDir('project-init-composer-name-not-exact', function (string $work_dir): void {
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
        $this->runInFreshWorkDir('project-init-composer-name-missing', function (string $work_dir): void {
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
        $this->runInFreshWorkDir('project-init-composer-update', function (string $work_dir): void {
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
        $this->runInFreshWorkDir('project-init-composer-dry-run', function (string $work_dir): void {
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

    public function test_execute_noInteraction_codeNameDefaultsToDirectoryName(): void
    {
        // The code name defaults to the current directory name as it is (ie. the project name
        // given to `composer create-project`).
        $this->runInFreshWorkDir('project-init-code-name-default', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertStringContainsString('code_name => project-init-code-name-default,', $tester->getDisplay());
        });
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function dataInvalidCodeNames(): array
    {
        return [
            ['ProjectInitUpperCase'],
            ['project_init_underscore'],
            ['project-init.dot'],
            ['project-init--double-hyphens'],
            ['project-init-trailing-hyphen-'],
        ];
    }

    #[DataProvider('dataInvalidCodeNames')]
    public function test_execute_noInteraction_directoryNameIsInvalidAsCodeName(string $dir_name): void
    {
        // Only lowercase letters, digits and hyphens are allowed as the code name.
        $this->runInFreshWorkDir($dir_name, function (string $work_dir) use ($dir_name): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString("`{$dir_name}` is invalid as an application code name (only lowercase letters, digits and hyphens are allowed)", $tester->getDisplay());
            $this->assertFileDoesNotExist("{$work_dir}/app");
        });
    }

    public function test_execute_interactive_directoryNameIsInvalidAsCodeName_isAskedAgain(): void
    {
        $this->runInFreshWorkDir('project_init_code_name_ask_again', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $tester->setInputs(array_merge(
                ['', 'fixed-code-name'], // Code name -> default (invalid directory name), so it is asked again
                array_slice($this->minimalInteractiveInputs(['', 'y']), 1),
            ));
            $status  = $tester->execute([], ['interactive' => true]);
            $display = $tester->getDisplay();
            $this->assertSame(0, $status);
            $this->assertStringContainsString('`project_init_code_name_ask_again` is invalid as an application code name', $display);
            $this->assertSame('fixed-code-name/fixed-code-name', json_decode(file_get_contents("{$work_dir}/composer.json"), true)['name']);
        });
    }

    public function test_execute_noInteraction_vendorDefaultsToCodeName(): void
    {
        $this->runInFreshWorkDir('project-init-vendor-default', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertStringContainsString('vendor => project-init-vendor-default,', $tester->getDisplay());
            $this->assertSame('project-init-vendor-default/project-init-vendor-default', json_decode(file_get_contents("{$work_dir}/composer.json"), true)['name']);
        });
    }

    public function test_execute_invalidVendorViaOption(): void
    {
        $this->runInFreshWorkDir('project-init-vendor-invalid', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--vendor' => 'Invalid Vendor'], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString('`Invalid Vendor` is invalid as a Composer vendor name', $tester->getDisplay());
            $this->assertFileDoesNotExist("{$work_dir}/app");
        });
    }

    public function test_execute_interactive_invalidVendorIsAskedAgain(): void
    {
        $this->runInFreshWorkDir('project-init-vendor-ask-again', function (string $work_dir): void {
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

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataValidLocales(): array
    {
        return [
            ['en', 'en'],
            ['ja_JP', 'ja_JP'],
            ['ja-JP', 'ja_JP'],
            ['JA_jp', 'ja_JP'],
            ['zh_Hant_TW', 'zh_Hant_TW'],
        ];
    }

    #[DataProvider('dataValidLocales')]
    public function test_execute_noInteraction_validLocale_isCanonicalized(string $given, string $expect): void
    {
        $this->runInFreshWorkDir('project-init-valid-locale', function () use ($given, $expect): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--locale' => $given, '--dry-run' => true], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertStringContainsString("locale => {$expect},", $tester->getDisplay());
        });
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function dataInvalidLocales(): array
    {
        return [
            ['xx'],
            ['ja_XX'],
            ['japanese'],
            ['C'],
        ];
    }

    #[DataProvider('dataInvalidLocales')]
    public function test_execute_noInteraction_invalidLocale(string $locale): void
    {
        $this->runInFreshWorkDir('project-init-invalid-locale', function (string $work_dir) use ($locale): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--locale' => $locale], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString("`{$locale}` is invalid as a locale", $tester->getDisplay());
            $this->assertFileDoesNotExist("{$work_dir}/app");
        });
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataValidTimezones(): array
    {
        return [
            ['UTC', 'UTC'],
            ['Asia/Tokyo', 'Asia/Tokyo'],
            ['asia/tokyo', 'Asia/Tokyo'],
            ['Japan', 'Japan'], // backward compatible identifier
        ];
    }

    #[DataProvider('dataValidTimezones')]
    public function test_execute_noInteraction_validTimezone_isCanonicalized(string $given, string $expect): void
    {
        $this->runInFreshWorkDir('project-init-valid-timezone', function () use ($given, $expect): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--timezone' => $given, '--dry-run' => true], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertStringContainsString("timezone => {$expect},", $tester->getDisplay());
        });
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function dataInvalidTimezones(): array
    {
        return [
            ['Foo/Bar'],
            ['JST'],
            ['+09:00'],
        ];
    }

    #[DataProvider('dataInvalidTimezones')]
    public function test_execute_noInteraction_invalidTimezone(string $timezone): void
    {
        $this->runInFreshWorkDir('project-init-invalid-timezone', function (string $work_dir) use ($timezone): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--timezone' => $timezone], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString("`{$timezone}` is invalid as a timezone", $tester->getDisplay());
            $this->assertFileDoesNotExist("{$work_dir}/app");
        });
    }

    public function test_execute_interactive_invalidLocaleAndTimezone_areAskedAgain(): void
    {
        $this->runInFreshWorkDir('project-init-locale-timezone-ask-again', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $tester->setInputs(array_merge(
                ['', ''],                         // Code name, Vendor -> defaults
                ['japanese', 'ja-JP'],            // Locale -> invalid, so it is asked again
                ['JST', 'asia/tokyo'],            // Timezone -> invalid, so it is asked again
                array_slice($this->minimalInteractiveInputs(['', 'y']), 4),
            ));
            $status  = $tester->execute([], ['interactive' => true]);
            $display = $tester->getDisplay();
            $this->assertSame(0, $status);
            $this->assertStringContainsString('`japanese` is invalid as a locale', $display);
            $this->assertStringContainsString('`JST` is invalid as a timezone', $display);
            $this->assertStringContainsString('locale => ja_JP,', $display);
            $this->assertStringContainsString('timezone => Asia/Tokyo,', $display);
        });
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function dataReservedDomains(): array
    {
        return [
            ['localhost'],
            ['LocalHost'],
            ['localhost.'],
            ['traefik.localhost'],
            ['Traefik.Localhost'],
        ];
    }

    #[DataProvider('dataReservedDomains')]
    public function test_execute_noInteraction_reservedDomain(string $domain): void
    {
        $this->runInFreshWorkDir('project-init-reserved-domain', function (string $work_dir) use ($domain): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--domain' => $domain], ['interactive' => false]);
            $this->assertSame(1, $status);
            $this->assertStringContainsString("`{$domain}` is reserved, so please use another domain (ex project-init-reserved-domain.localhost).", $tester->getDisplay());
            $this->assertFileDoesNotExist("{$work_dir}/app");
        });
    }

    public function test_execute_interactive_reservedDomain_isAskedAgain(): void
    {
        $this->runInFreshWorkDir('project-init-domain-ask-again', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $tester->setInputs(array_merge(
                ['', '', '', ''],                           // Code name, Vendor, Locale, Timezone -> defaults
                ['localhost', 'traefik.localhost', 'foo.localhost'], // Domain -> reserved twice, so it is asked again
                array_slice($this->minimalInteractiveInputs(['', 'y']), 5),
            ));
            $status  = $tester->execute([], ['interactive' => true]);
            $display = $tester->getDisplay();
            $this->assertSame(0, $status);
            $this->assertStringContainsString('`localhost` is reserved', $display);
            $this->assertStringContainsString('`traefik.localhost` is reserved', $display);
            $this->assertStringContainsString('domain => foo.localhost,', $display);
        });
    }

    public function test_execute_localhostDomain_hostsFileIsIfNeeded(): void
    {
        $this->runInFreshWorkDir('project-init-localhost-domain', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(0, $status);

            $post_create = file_get_contents("{$work_dir}/.devcontainer/post_create_command.sh");
            $this->assertStringContainsString("'project-init-localhost-domain.localhost' usually resolves to 127.0.0.1 without editing your hosts file.", $post_create);
            $this->assertStringContainsString("If needed (ex. your browser/OS can not resolve it), write '127.0.0.1 project-init-localhost-domain.localhost' in your hosts file.", $post_create);
            $this->assertStringNotContainsString('Please write', $post_create);
        });
    }

    public function test_execute_otherDomain_hostsFileIsRequired(): void
    {
        $this->runInFreshWorkDir('project-init-other-domain', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--domain' => 'local.example.com'], ['interactive' => false]);
            $this->assertSame(0, $status);

            $post_create = file_get_contents("{$work_dir}/.devcontainer/post_create_command.sh");
            $this->assertStringContainsString("Please write '127.0.0.1 local.example.com' in your hosts file.", $post_create);
            $this->assertStringNotContainsString('If needed', $post_create);
        });
    }

    public function test_execute_noInteraction_databaseNamesDefaultToSnakeCaseOfCodeName(): void
    {
        $this->runInFreshWorkDir('project-init-snake-default', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--database' => 'mysql', '--dry-run' => true], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            $this->assertStringContainsString('code_name => project-init-snake-default,', $display);
            $this->assertStringContainsString('db_name => project_init_snake_default,', $display);
            $this->assertStringContainsString('db_user => project_init_snake_default,', $display);
        });
    }

    public function test_execute_noInteraction_withoutDatabase_databaseNameDefaultsToSnakeCaseOfCodeName(): void
    {
        // Even when database is not used, the (unused) database name is set to the snake case of the
        // code name, since the skelton templates refer to it.
        $this->runInFreshWorkDir('project-init-snake-unused', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--dry-run' => true], ['interactive' => false]);
            $this->assertSame(0, $status);

            $display = $tester->getDisplay();
            $this->assertStringContainsString('db_name => project_init_snake_unused,', $display);
        });
    }

    /**
     * @return array<int, array{0: array<string, string>, 1: string, 2: string}>
     */
    public static function dataPgsqlIdentifiers(): array
    {
        return [
            [[], 'project_init_pgsql_quoted', 'project_init_pgsql_quoted'],
            [['--database-name' => 'my-db', '--database-user' => '3d-user'], 'my-db', '3d-user'],
        ];
    }

    #[DataProvider('dataPgsqlIdentifiers')]
    public function test_execute_pgsql_identifiersAreQuoted(array $options, string $db_name, string $db_user): void
    {
        // PostgreSQL requires identifiers containing hyphens or starting with digits to be quoted.
        $this->runInFreshWorkDir('project-init-pgsql-quoted', function (string $work_dir) use ($options, $db_name, $db_user): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(array_merge(['--database' => 'pgsql'], $options), ['interactive' => false]);
            $this->assertSame(0, $status);

            $sql = file_get_contents("{$work_dir}/.devcontainer/docker/pgsql/initdb.d/001_create_database.sql");
            $this->assertStringContainsString("CREATE USER \"{$db_user}\" WITH PASSWORD", $sql);
            $this->assertStringContainsString("ALTER ROLE \"{$db_user}\" WITH SUPERUSER;", $sql);
            $this->assertStringContainsString("CREATE DATABASE \"{$db_name}\" WITH OWNER = \"{$db_user}\"", $sql);
        });
    }

    /**
     * @return array<int, array{0: array<string, string>, 1: string}>
     */
    public static function dataDefaultPasswords(): array
    {
        return [
            // The password defaults to the (default) user.
            [[], 'project_init_default_password'],
            // The password defaults to the given user.
            [['--database-user' => 'db_foo'], 'db_foo'],
            // The given password is used as it is.
            [['--database-pass' => 'db_secret'], 'db_secret'],
        ];
    }

    #[DataProvider('dataDefaultPasswords')]
    public function test_execute_noInteraction_databasePasswordDefaultsToUser(array $options, string $db_pass): void
    {
        $this->runInFreshWorkDir('project-init-default-password', function (string $work_dir) use ($options, $db_pass): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(array_merge(['--database' => 'mysql'], $options), ['interactive' => false]);
            $this->assertSame(0, $status);

            $env = file_get_contents("{$work_dir}/.env");
            $this->assertStringContainsString("DB_PASSWORD={$db_pass}\n", $env);
        });
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function dataRootPasswords(): array
    {
        return [
            ['mysql', 'MYSQL_ROOT_PASSWORD: root'],
            ['mariadb', 'MARIADB_ROOT_PASSWORD: root'],
            ['pgsql', 'POSTGRES_PASSWORD: root'],
        ];
    }

    #[DataProvider('dataRootPasswords')]
    public function test_execute_rootPasswordIsSameAsRootUser(string $database, string $expect): void
    {
        $this->runInFreshWorkDir('project-init-root-password', function (string $work_dir) use ($database, $expect): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--database' => $database], ['interactive' => false]);
            $this->assertSame(0, $status);

            $compose = file_get_contents("{$work_dir}/.devcontainer/docker-compose.yml");
            $this->assertSame(2, substr_count($compose, $expect)); // for local development and unit test
            $this->assertStringNotContainsString('P@ssw0rd', $compose);
        });
    }

    public function test_execute_traefikPortsArePublishedOnlyOnLoopback(): void
    {
        $this->runInFreshWorkDir('project-init-traefik-loopback', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute([], ['interactive' => false]);
            $this->assertSame(0, $status);

            $initialize = file_get_contents("{$work_dir}/.devcontainer/initialize_command.sh");
            $this->assertStringContainsString('-p 127.0.0.1:80:80 \\', $initialize);
            $this->assertStringContainsString('-p 127.0.0.1:443:443 \\', $initialize);
            $this->assertDoesNotMatchRegularExpression('/-p (80|443):/', $initialize);
        });
    }

    public function test_execute_interactive_reviewShowsDatabasePassword(): void
    {
        $this->runInFreshWorkDir('project-init-review-passwords', function (): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $tester->setInputs([
                '', '', '', '', '',          // Code name, Vendor, Locale, Timezone, Domain -> defaults
                '', 'db_user', '',           // DB Name -> default, DB User -> db_user, DB Password -> default (= DB User)
                'n',                         // Auth -> no
                '',                          // View -> default
                '',                          // Session -> default
                '', 'y',                     // Review -> yes, Are you really sure? -> yes
            ]);
            $status  = $tester->execute(['--database' => 'mysql', '--cache' => 'memcached'], ['interactive' => true]);
            $display = $tester->getDisplay();
            $this->assertSame(0, $status);
            $this->assertMatchesRegularExpression('/\|\s+DB Password \(For Local\)\s+\|\s+db_user\s+\|/', $display);
            // The memcached for the cache store is used without authentication, so nothing is asked for it.
            $this->assertStringNotContainsString('Memcached User', $display);
            $this->assertStringNotContainsString('Memcached Password', $display);
        });
    }

    public function test_execute_interactive_reviewShowsAuthPassword_andItIsHashedInGeneratedFile(): void
    {
        // The auth password is shown as it is in the review, and hashed only in the generated file.
        $this->runInFreshWorkDir('project-init-review-auth-password', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $tester->setInputs([
                '', '', '', '', '', // Code name, Vendor, Locale, Timezone, Domain -> defaults
                'n',                // Database -> no (Auth is given via options)
                '',                 // View -> default
                'n',                // Cache -> no
                '',                 // Session -> default
                '', 'y',            // Review -> yes, Are you really sure? -> yes
            ]);
            $status  = $tester->execute(['--auth' => true, '--auth-name' => 'Admin', '--auth-email' => 'admin@example.com', '--auth-password' => 'P@ssw0rd1'], ['interactive' => true]);
            $display = $tester->getDisplay();
            $this->assertSame(0, $status);
            $this->assertMatchesRegularExpression('/\|\s+Auth Password\s+\|\s+P@ssw0rd1\s+\|/', $display);

            $auth = file_get_contents("{$work_dir}/app/config/auth.php");
            $this->assertStringNotContainsString('P@ssw0rd1', $auth);
            $this->assertSame(1, preg_match("/'email' => 'admin@example\\.com', 'password' => '(?<hash>[^']+)'/", $auth, $matches));
            $this->assertTrue(Password::verify('P@ssw0rd1', $matches['hash']));
        });
    }

    /**
     * @return array<int, array{0: array<string, string>, 1: bool, 2: bool}>
     */
    public static function dataMemcachedContainers(): array
    {
        return [
            // [options, memcached-cache exists, memcached-session exists]
            [['--cache' => 'memcached', '--session' => 'native'], true, false],
            [['--cache' => 'file', '--session' => 'memcached'], false, true],
            [['--session' => 'memcached'], false, true], // without cache store
            [['--cache' => 'memcached', '--session' => 'memcached'], true, true],
            [['--cache' => 'file', '--session' => 'native'], false, false],
            [[], false, false], // without cache store, native session
        ];
    }

    #[DataProvider('dataMemcachedContainers')]
    public function test_execute_noInteraction_memcachedContainersAreSeparatedForCacheAndSession(array $options, bool $cache, bool $session): void
    {
        // The memcached for the cache store and the session storage are separated, since they have
        // different purposes.
        $this->runInFreshWorkDir('project-init-memcached-containers', function (string $work_dir) use ($options, $cache, $session): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute($options, ['interactive' => false]);
            $this->assertSame(0, $status);

            $compose = file_get_contents("{$work_dir}/.devcontainer/docker-compose.yml");
            $this->assertSame($cache, str_contains($compose, "\n  memcached-cache:\n"));
            $this->assertSame($session, str_contains($compose, "\n  memcached-session:\n"));
            $this->assertStringNotContainsString("\n  memcached:\n", $compose);

            // The memcached extension is installed when either of them is used.
            foreach (['php-fpm', 'workspace'] as $container) {
                $dockerfile = file_get_contents("{$work_dir}/.devcontainer/docker/{$container}/Dockerfile");
                $this->assertSame($cache || $session, str_contains($dockerfile, 'pecl install                            memcached'), $container);
            }
        });
    }

    public function test_execute_noInteraction_memcachedCacheStoreIsUsedWithoutAuthentication(): void
    {
        $this->runInFreshWorkDir('project-init-memcached-no-auth', function (string $work_dir): void {
            $tester = $this->getCommandTester(ProjectInitCommand::NAME);
            $status = $tester->execute(['--cache' => 'memcached'], ['interactive' => false]);
            $this->assertSame(0, $status);
            $this->assertStringNotContainsString('memcached_user', $tester->getDisplay());

            $cache = file_get_contents("{$work_dir}/app/config/cache.php");
            $this->assertStringContainsString("'dsn'      => Env::promise('CACHE_MEMCACHED_DSN'),", $cache);
            $this->assertStringContainsString("// 'username'      => Env::promise('CACHE_MEMCACHED_USERNAME'),", $cache);
            $this->assertStringContainsString("// 'password'      => Env::promise('CACHE_MEMCACHED_PASSWORD'),", $cache);

            $env = file_get_contents("{$work_dir}/.env");
            $this->assertStringContainsString("CACHE_MEMCACHED_DSN=memcached://memcached-cache:11211\n", $env);
            $this->assertStringContainsString("# CACHE_MEMCACHED_USERNAME=\n# CACHE_MEMCACHED_PASSWORD=\n", $env);
            $this->assertDoesNotMatchRegularExpression('/^(CACHE|SESSION)_MEMCACHED_(USERNAME|PASSWORD)=/m', $env);
            $this->assertStringNotContainsString('SESSION_MEMCACHED_DSN', $env);
        });
    }
}
