<?php

declare(strict_types=1);

namespace Rebet\Application\Console\Command\Project;

use Override;
use Rebet\Auth\Password;
use Rebet\Console\Command\Command;
use Rebet\Inflection\Inflector;
use Rebet\Tools\Template\Letterpress;
use Rebet\Tools\Testable\System;
use Rebet\Tools\Utility\Path;
use Rebet\Tools\Utility\Strings;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputOption;

/**
 * Init Command Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ProjectInitCommand extends Command
{
    public const NAME        = 'project:init';
    public const DESCRIPTION = 'Initialize a new Rebet application';
    public const OPTIONS     = [
        [['domain', 'd'  ], null, InputOption::VALUE_OPTIONAL, 'Application domain for local development. (default: localhost)'],
        [['locale', 'l'  ], null, InputOption::VALUE_OPTIONAL, 'Default application locale. (default: the system locale, ie. locale_get_default())'],
        [['timezone', 't'  ], null, InputOption::VALUE_OPTIONAL, 'Default application timezone. (default: the system timezone, ie. date_default_timezone_get(), falls back to UTC)'],
        [['database', 'db' ], null, InputOption::VALUE_OPTIONAL, 'Database product. (choices: sqlite, mysql, mariadb, pgsql / default: mysql)'],
        [['database-name', 'dbn'], null, InputOption::VALUE_OPTIONAL, 'Database name for local development. (default: the application code name)'],
        [['database-user', 'dbu'], null, InputOption::VALUE_OPTIONAL, 'Database user for local development. (default: the application code name)'],
        [['database-pass', 'dbp'], null, InputOption::VALUE_OPTIONAL, 'Database password for local development. (default: P@ssw0rd)'],
        [['auth'                 ], 'a', InputOption::VALUE_NONE, 'Use user auth (when do not use database then use ArrayProvider as read only authentication)'],
        [['auth-name', 'an' ], null, InputOption::VALUE_OPTIONAL, 'Auth user name for local development (only when --auth is used without a database).'],
        [['auth-email', 'ae' ], null, InputOption::VALUE_OPTIONAL, 'Auth user email for local development (only when --auth is used without a database).'],
        [['auth-password', 'ap' ], null, InputOption::VALUE_OPTIONAL, 'Auth user password for local development (only when --auth is used without a database).'],
        [['view', 'v'  ], null, InputOption::VALUE_OPTIONAL, 'View template engine. (choices: twig, blade / default: twig)'],
        [['cache', 'c'  ], null, InputOption::VALUE_OPTIONAL, 'Cache store product. (choices: apcu, file, memcached, redis, and also database when a database is used / default: memcached)'],
        [['memcached-user', 'mu' ], null, InputOption::VALUE_OPTIONAL, 'Memcached user for local development. (default: the application code name)'],
        [['memcached-pass', 'mp' ], null, InputOption::VALUE_OPTIONAL, 'Memcached password for local development. (default: P@ssw0rd)'],
        [['session', 's'  ], null, InputOption::VALUE_OPTIONAL, 'Session storage. (choices: native, database (when a database is used), memcached, redis, mongodb / default: native)'],
        [['dry-run'              ], null, InputOption::VALUE_NONE, 'Show the settings and the list of files that would be generated, without writing anything.'],
    ];

    /**
     * Supported database products [driver => label], matching the `.devcontainer/docker/{driver}`
     * skelton directories.
     *
     * @var array<string, string>
     */
    public const SUPPORTED_DATABASES = [
        'sqlite'  => 'SQLite 3',
        'mysql'   => 'MySQL',
        'mariadb' => 'MariaDB',
        'pgsql'   => 'PostgreSQL',
    ];

    /**
     * Supported cache store products [driver => label]. The `database` choice is only offered when
     * a database is actually used (see the `use_db` config).
     *
     * @var array<string, string>
     */
    public const SUPPORTED_CACHES = [
        'apcu'      => 'APCu',
        'database'  => 'Database',
        'file'      => 'File System',
        'memcached' => 'Memcached',
        'redis'     => 'Redis',
    ];

    /**
     * Supported session storage handlers [handler => label]. The `database` choice is only offered
     * when a database is actually used (see the `use_db` config).
     *
     * @var array<string, string>
     */
    public const SUPPORTED_SESSIONS = [
        'native'    => 'Native (File)',
        'database'  => 'Database',
        'memcached' => 'Memcached',
        'redis'     => 'Redis',
        'mongodb'   => 'MongoDB',
    ];

    /**
     * Composer packages to `composer require` based on the collected $configs, grouped by the
     * $configs key that decides whether each package is required (see resolveComposerPackages()).
     *
     * @var array<string, array<string, string>>
     */
    public const COMPOSER_REQUIRE = [
        'session' => [
            'mongodb' => 'mongodb/mongodb',
            'redis'   => 'predis/predis',
        ],
        'cache'   => [
            'redis' => 'predis/predis',
        ],
        'view'    => [
            'twig'  => 'twig/twig',
            'blade' => 'illuminate/view',
        ],
    ];

    /**
     * Composer packages to always `composer require --dev`, regardless of $configs (see
     * resolveComposerPackages()).
     *
     * @var array<string, array<int, string>>
     */
    public const COMPOSER_REQUIRE_DEV = [
        'always' => [
            "friendsofphp/php-cs-fixer",
            "phpstan/phpstan",
            "phpunit/phpunit",
            "psy/psysh",
        ],
    ];

    /**
     * The wizard steps this command asks, in order, as [step method => step label]. Used to drive
     * the initial pass (see handle()) and to let the user pick a step to redo (see reviewConfigs()).
     *
     * @var array<string, string>
     */
    public const STEPS = [
        'stepDefaults' => 'Setup Your Application Default Configs',
        'stepDomain'   => 'Setup Your Application Domain for Local Development',
        'stepDatabase' => 'Setup Database For Local Development Configs',
        'stepAuth'     => 'Setup Auth Configs',
        'stepView'     => 'Setup View Configs',
        'stepCache'    => 'Setup Cache Store For Local Development Configs',
        'stepSession'  => 'Setup Session Storage Configs',
    ];

    /**
     * The path to the skeltons directory that this command renders (via Letterpress) into the new
     * application's `app/` directory.
     *
     * @var string
     */
    protected string $skeltons_dir;

    /**
     * Create the project:init command.
     *
     * @param string $skeltons_dir path to the skeltons directory that provides the template files
     */
    public function __construct(string $skeltons_dir)
    {
        parent::__construct(null, null);
        $this->skeltons_dir = Path::normalize($skeltons_dir);
    }

    #[Override]
    protected function handle()
    {
        // 参考
        // @see https://symfony.com/doc/current/console.html
        // @see https://symfony.com/doc/current/components/console/helpers/questionhelper.html
        // @see https://techblog.istyle.co.jp/archives/97
        // @see https://github.com/laravel/framework/blob/7.x/src/Illuminate/Foundation/Console/EnvironmentCommand.php

        $configs['cwd']          = $cwd = Path::normalize(getcwd());
        $configs['skeltons_dir'] = $this->skeltons_dir;

        if (!$this->checkEnvironment($cwd)) {
            return 1;
        }

        $total_step = count(static::STEPS);

        $this->comment('===========================================');
        $this->comment(' Welcome to Rebet Project Initializing ');
        $this->comment('===========================================');
        $this->comment('Please answer questions below.');

        $step = 0;
        foreach (static::STEPS as $method => $label) {
            $configs = $this->runStep(++$step, $total_step, $label, $method, $configs);
            if ($configs === null) {
                return 1;
            }
        }

        // @todo Mail settings use mailpit for local development

        $this->writeln('');
        $this->comment('DEBUG: You are inputed -------');
        $this->comment(Strings::stringify($configs));
        $this->comment('-----------------------');

        // Let the user review the collected settings, redo any step that needs fixing, or abort,
        // before anything is actually written. Skipped entirely under --no-interaction, since
        // there is nobody to review/confirm anything.
        if ($this->input->isInteractive()) {
            $configs = $this->reviewConfigs($configs, $total_step);
            if ($configs === null) {
                $this->comment('Aborted by user, nothing was done.');
                return 1;
            }
        }

        $code_name = $configs['code_name'];
        $dry_run   = (bool) $this->option('dry-run');

        $this->writeln('');
        $this->writeln($dry_run ? 'Previewing application files that would be generated from skeltons (dry-run, nothing is written)...' : 'Generating application files from skeltons...');
        $generated = $this->generate($this->skeltons_dir, $cwd, $configs, $dry_run, $this->excludedDatabaseDirs($configs));
        if ($dry_run) {
            foreach ($generated as $path) {
                $this->writeln("  - {$path}");
            }
        }
        $this->writeln('  ' . count($generated) . ' files ' . ($dry_run ? 'would be generated.' : 'generated.'));

        $require     = $this->resolveComposerPackages(static::COMPOSER_REQUIRE, $configs);
        $require_dev = $this->resolveComposerPackages(static::COMPOSER_REQUIRE_DEV, $configs);
        if (!empty($require) || !empty($require_dev)) {
            $this->writeln('');
            $this->writeln($dry_run ? 'Composer packages that would be required...' : 'Installing required Composer packages...');
            if (!empty($require)) {
                $this->writeln('  - composer require ' . implode(' ', $require));
            }
            if (!empty($require_dev)) {
                $this->writeln('  - composer require --dev ' . implode(' ', $require_dev));
            }
            if (!$dry_run) {
                $this->composerRequire($cwd, $require, false);
                $this->composerRequire($cwd, $require_dev, true);
            }
        }

        if ($dry_run) {
            $this->comment("Dry-run finished, nothing was written. Remove `--dry-run` to actually initialize the {$code_name} project.");
            return 0;
        }

        $this->writeln('');
        $this->info('-----------------------');
        $this->info("Let's type `code .` in terminal, then `Reopen in Container` on your VSCode");
        $this->info("to start development your application.");
        $this->info('-----------------------');

        $this->comment("Project {$code_name} initilized! Build something amazing.");
    }

    /**
     * Print a step header and run the given wizard step method (see static::STEPS).
     *
     * @param  int                       $step       1-based step number
     * @param  int                       $total_step
     * @param  string                    $label
     * @param  string                    $method     one of static::STEPS's keys
     * @param  array<string, mixed>      $configs
     * @return array<string, mixed>|null updated $configs, or null when the step failed (the
     *                                   failure reason has already been printed via $this->error())
     */
    protected function runStep(int $step, int $total_step, string $label, string $method, array $configs): array|null
    {
        $this->writeln('');
        $this->writeln("-------------------------------------------");
        $this->writeln("{$step}) {$label} ({$step}/{$total_step})");
        $this->writeln("-------------------------------------------");
        return $this->{$method}($configs);
    }

    /**
     * Show the collected $configs, then let the user confirm them, jump straight to a specific
     * step to fix it (by its step number), or abort, repeating until the user either confirms
     * (with a second "are you sure?" confirmation) or aborts (also with a second confirmation).
     *
     * @param  array<string, mixed>      $configs
     * @param  int                       $total_step
     * @return array<string, mixed>|null the confirmed $configs, or null when the user aborted
     */
    protected function reviewConfigs(array $configs, int $total_step): array|null
    {
        $methods = array_keys(static::STEPS);
        $labels  = array_values(static::STEPS);

        while (true) {
            $this->writeln('');
            $this->displayConfigs($configs);

            $choices = ['yes' => 'Yes, proceed with these settings'];
            foreach ($labels as $i => $label) {
                $choices[$i + 1] = ($i + 1) . ") Fix: {$label}";
            }
            $choices['abort'] = 'Abort (cancel initialization)';

            $picked = $this->choice("Are these settings OK? If not, type the step number to fix it. : ", $choices, null, 'yes');
            // ChoiceQuestion may resolve to either the chosen key or its value depending on the
            // key's type (an all-integer choice list resolves to the value), so normalize back to
            // the canonical choice key via a value => key reverse lookup.
            $action = array_key_exists($picked, $choices) ? $picked : (array_search($picked, $choices, true) ?: $picked);

            if ($action === 'abort') {
                if ($this->confirm("Are you sure you want to abort? Nothing will be initialized. [y/n] : ", false)) {
                    return null;
                }
                continue;
            }

            if ($action === 'yes') {
                if ($this->confirm("Are you really sure these settings are correct and ready to proceed? [y/n] : ", true)) {
                    return $configs;
                }
                continue;
            }

            // Otherwise $action is the 1-based step number to fix.
            $index   = ((int) $action) - 1;
            $configs = $this->runStep($index + 1, $total_step, $labels[$index], $methods[$index], $configs);
            if ($configs === null) {
                return null;
            }
        }
    }

    /**
     * Print the currently collected $configs as a human readable table, grouped by the wizard
     * step that asked each setting. Password-like values are masked.
     *
     * @param  array<string, mixed> $configs
     * @return void
     */
    protected function displayConfigs(array $configs): void
    {
        $yn     = fn($value) => $value ? 'Yes' : 'No';
        $mask   = '********';
        $use_db = $configs['use_db'] ?? false;
        $labels = array_values(static::STEPS);

        $groups = [
            [
                ['Application Code Name', $configs['code_name'] ?? ''],
                ['Locale', $configs['locale'] ?? ''],
                ['Timezone', $configs['timezone'] ?? ''],
            ],
            [
                ['Domain', $configs['domain'] ?? ''],
            ],
            array_values(array_filter([
                ['Use Database', $yn($use_db)],
                $use_db ? ['DB Product', $configs['database'] ?? '', true] : null,
                $use_db ? ['DB Name', $configs['db_name'] ?? '', true] : null,
                isset($configs['db_user']) ? ['DB User', $configs['db_user'], true] : null,
                isset($configs['db_pass']) ? ['DB Password', $mask, true] : null,
            ])),
            array_values(array_filter([
                ['Use Auth', $yn($configs['use_auth'] ?? false)],
                isset($configs['auth_name']) ? ['Auth Name', $configs['auth_name'], true] : null,
                isset($configs['auth_email']) ? ['Auth Email', $configs['auth_email'], true] : null,
                isset($configs['auth_password']) ? ['Auth Password', $mask, true] : null,
            ])),
            [
                ['View Engine', $configs['view'] ?? ''],
            ],
            array_values(array_filter([
                ['Use Cache', $yn($configs['use_cache'] ?? false)],
                ($configs['use_cache'] ?? false) ? ['Cache Store', $configs['cache'] ?? '', true] : null,
                ($configs['cache'] ?? null) === 'memcached' && isset($configs['memcached_user']) ? ['Memcached User', $configs['memcached_user'], true] : null,
                isset($configs['memcached_pass']) ? ['Memcached Password', $mask, true] : null,
            ])),
            [
                ['Session Storage', $configs['session'] ?? ''],
            ],
        ];

        $rows = [];
        foreach ($groups as $i => $group) {
            $rows[] = [new TableCell("<comment>" . ($i + 1) . ") {$labels[$i]}</comment>", ['colspan' => 2])];
            foreach ($group as $setting) {
                $indent = ($setting[2] ?? false) ? '    ' : '  ';
                $rows[] = ["{$indent}{$setting[0]}", $setting[1]];
            }
        }

        $this->comment('Current settings -------------------------');
        $this->table(['Setting', 'Value'], $rows);
    }

    /**
     * Wizard step: application code name, locale and timezone.
     *
     * @param  array<string, mixed> $configs
     * @return array<string, mixed>
     */
    protected function stepDefaults(array $configs): array
    {
        $configs['code_name'] = $this->ask("* Application Code Name : ", null, true, Inflector::kebabize(basename($configs['cwd'])));
        // Same fallback as the library default (see Rebet\Application\App::defaultConfig()).
        $configs['locale']   = $this->ask("* Default Locale        : [" . locale_get_default() . "] ", 'locale', true, locale_get_default());
        $configs['timezone'] = $this->ask("* Default Timezone      : [" . (date_default_timezone_get() ?: 'UTC') . "] ", 'timezone', true, date_default_timezone_get() ?: 'UTC');
        return $configs;
    }

    /**
     * Wizard step: application domain for local development.
     *
     * @param  array<string, mixed> $configs
     * @return array<string, mixed>
     */
    protected function stepDomain(array $configs): array
    {
        $code_name = $configs['code_name'];
        $this->comment(" - If you already have production domain, then type it with prefix `local.` (ex local.{$code_name}.com)");
        $this->comment(" - If you don't have production domain yet, then type app name with suffix `.local` (ex {$code_name}.local)");
        $this->comment(" - If you don't care local development doamin, then type `localhost`");
        // Same fallback as the skelton's own `APP_DOMAIN` default (see skeltons/app/core/.lp.env).
        $configs['domain'] = $domain = $this->ask("* Application Domain for Local Development : [localhost] ", 'domain', true, 'localhost');
        // The docker/nginx skelton templates refer to this same value as `site_domain`.
        $configs['site_domain'] = $domain;
        return $configs;
    }

    /**
     * Wizard step: database product and credentials for local development (or none at all).
     *
     * @param  array<string, mixed>      $configs
     * @return array<string, mixed>|null null when an explicitly given --database value is invalid
     */
    protected function stepDatabase(array $configs): array|null
    {
        $code_name = $configs['code_name'];
        unset($configs['db_user'], $configs['db_pass']);

        $use_db = false;
        if ($this->option('database') || $this->confirm("Will you use database? [y/n] : ")) {
            // Reject an explicitly given but invalid --database value before it ever reaches
            // choice()'s non-interactive fallback, which would otherwise silently substitute the
            // default below instead of failing (Command::choice() cannot tell "not given" apart
            // from "given but unresolved" once it falls back to the underlying ChoiceQuestion).
            if (($given = $this->option('database')) && !$this->requireValidChoice('database', $given, static::SUPPORTED_DATABASES)) {
                return null;
            }
            $configs['database'] = $this->choice("* DB Product  : ", static::SUPPORTED_DATABASES, 'database', 'mysql');
            $is_sqlite           = $configs['database'] === 'sqlite';
            $configs['db_name']  = $this->ask("* DB Name     : [{$code_name}] ", 'database-name', true, $code_name);
            if (!$is_sqlite) {
                $configs['db_user'] = $this->ask("* DB User     : [{$code_name}] ", 'database-user', true, $code_name);
                $configs['db_pass'] = $this->ask("* DB Password : [P@ssw0rd] ", 'database-pass', true, 'P@ssw0rd');
            }
            $use_db = true;
        }
        $configs['use_db'] = $use_db;
        if (!$use_db) {
            $configs['database'] = 'mysql';
            $configs['db_name']  = $code_name;
        }
        return $configs;
    }

    /**
     * Wizard step: user auth (and, when not using a database, the single ArrayProvider user).
     *
     * @param  array<string, mixed>      $configs
     * @return array<string, mixed>|null null when `--auth` is used without a database and without
     *                                   `--auth-name`/`--auth-email`/`--auth-password` under `--no-interaction`
     */
    protected function stepAuth(array $configs): array|null
    {
        unset($configs['auth_name'], $configs['auth_email'], $configs['auth_password']);
        $use_db = $configs['use_db'] ?? false;

        $use_auth = false;
        if ($this->option('auth') || $this->confirm("Will you use user auth? [y/n] : ")) {
            if (!$use_db) {
                // Unlike the other questions, there is no sensible default for a user's name/email/
                // password, so `--auth` without a database requires these to be given explicitly
                // (via --auth-name/--auth-email/--auth-password) when running with --no-interaction.
                if (!$this->input->isInteractive() && !($this->option('auth-name') && $this->option('auth-email') && $this->option('auth-password'))) {
                    $this->error('`--auth` without a database requires `--auth-name`, `--auth-email` and `--auth-password` when running with `--no-interaction`.');
                    return null;
                }

                $this->comment("You do not use database, so set ArrayProvider as read only authentication.");
                $this->comment("Please input an authentication user information that will be written in auth.php configuration file.");
                $this->writeln("NOTE: If you want to change the password or add new user then you can use Rebet assistant `hash:password` command to create password hash.");
                $configs['auth_name']     = $this->ask("* Name             : ", 'auth-name', true);
                $configs['auth_email']    = $this->ask("* Email            : ", 'auth-email', true);
                $configs['auth_password'] = Password::hash($this->option('auth-password') ?: $this->password("* Password         : ", "* Confirm Password : "));
            }
            $use_auth = true;
        }
        $configs['use_auth'] = $use_auth;
        return $configs;
    }

    /**
     * Wizard step: view template engine.
     *
     * @param  array<string, mixed> $configs
     * @return array<string, mixed>
     */
    protected function stepView(array $configs): array
    {
        $configs['view'] = $this->choice("* View Engine : ", [
            'twig'  => 'Twig',
            'blade' => 'Larabel Blade',
        ], 'view', 'twig');
        return $configs;
    }

    /**
     * Wizard step: cache store product for local development (or none at all).
     *
     * @param  array<string, mixed>      $configs
     * @return array<string, mixed>|null null when an explicitly given --cache value is invalid
     */
    protected function stepCache(array $configs): array|null
    {
        $code_name = $configs['code_name'];
        $use_db    = $configs['use_db'] ?? false;
        unset($configs['memcached_user'], $configs['memcached_pass']);

        $use_cache = false;
        if ($this->option('cache') || $this->confirm("Will you use cache store? [y/n] : ")) {
            $cache_choices = static::SUPPORTED_CACHES;
            if (!$use_db) {
                unset($cache_choices['database']);
            }
            // Same reasoning as the --database check above: reject an explicitly given but
            // invalid --cache value before it can silently fall back to the default below.
            if (($given = $this->option('cache')) && !$this->requireValidChoice('cache', $given, $cache_choices)) {
                return null;
            }
            $configs['cache'] = $this->choice("* Cache Store : ", $cache_choices, 'cache', 'memcached');
            if ($configs['cache'] == 'memcached') {
                $configs['memcached_user'] = $this->ask("* Memcached User     : [{$code_name}] ", 'memcached-user', true, $code_name);
                $configs['memcached_pass'] = $this->ask("* Memcached Password : [P@ssw0rd] ", 'memcached-pass', true, 'P@ssw0rd');
            }
            $use_cache = true;
        }
        $configs['use_cache'] = $use_cache;
        if (!$use_cache) {
            $configs['cache']          = 'memcached';
            $configs['memcached_user'] = $code_name;
        }
        return $configs;
    }

    /**
     * Wizard step: session storage handler.
     *
     * @param  array<string, mixed>      $configs
     * @return array<string, mixed>|null null when an explicitly given --session value is invalid
     */
    protected function stepSession(array $configs): array|null
    {
        $use_db = $configs['use_db'] ?? false;

        $session_choices = static::SUPPORTED_SESSIONS;
        if (!$use_db) {
            unset($session_choices['database']);
        }
        // Same reasoning as the --database/--cache checks above: reject an explicitly given but
        // invalid --session value before it can silently fall back to the default below.
        if (($given = $this->option('session')) && !$this->requireValidChoice('session', $given, $session_choices)) {
            return null;
        }
        $configs['session'] = $this->choice("* Session Storage : ", $session_choices, 'session', 'native');
        return $configs;
    }

    /**
     * Check that the environment this command is running in is suitable for `project:init`, and
     * print an error otherwise.
     *
     *  - The skeltons directory (that this command renders from) must exist.
     *  - The given directory must be the root of an existing Composer project (ie. it must contain
     *    a `composer.json`), since `project:init` only adds the Rebet application skeleton to an
     *    existing Composer project rather than creating a brand-new one.
     *  - The given directory must not already be initialized (see `existingSkeltonEntries()`).
     *
     * @param  string $cwd
     * @return bool
     */
    protected function checkEnvironment(string $cwd): bool
    {
        if (!is_dir($this->skeltons_dir)) {
            $this->error("Rebet skeltons directory `{$this->skeltons_dir}` not exists.");
            return false;
        }

        $composer_json = Path::normalize("{$cwd}/composer.json");
        if (!file_exists($composer_json)) {
            $this->error("This directory does not seem to be a Composer project (`{$composer_json}` not found).");
            $this->error('`' . static::NAME . '` must be run from the root of an existing Composer project.');
            return false;
        }

        $existing = $this->existingSkeltonEntries($cwd);
        if (!empty($existing)) {
            $this->error("This directory seems to already be initialized (`" . implode('`, `', $existing) . "` already exists).");
            $this->error('`' . static::NAME . '` is only for setting up a brand-new Rebet application, so nothing was done.');
            return false;
        }

        return true;
    }

    /**
     * Validate that the given value (the result of a `Command::choice()` call) is one of the
     * given choice keys, and print an error otherwise.
     *
     * This check exists because `Command::choice()` has no way to reject an invalid value: when
     * the given `--{$option_name}` value does not resolve via `viaOption()` (eg. a typo) and the
     * question is not interactive (`--no-interaction`), Symfony's QuestionHelper silently falls
     * back to `null` instead of failing, since the underlying ChoiceQuestion has no default set.
     *
     * @param  string                $option_name CLI option name, only used for the error message
     * @param  mixed                 $value       the value returned by Command::choice()
     * @param  array<string, string> $choices
     * @return bool
     */
    protected function requireValidChoice(string $option_name, $value, array $choices): bool
    {
        if (is_string($value) && array_key_exists($value, $choices)) {
            return true;
        }

        $this->error("Invalid value `" . $this->option($option_name) . "` given via `--{$option_name}`. Choices are: `" . implode('`, `', array_keys($choices)) . "`.");
        return false;
    }

    /**
     * Get the paths, under the given directory, that already exist and correspond to one of the
     * top-level entries of the skeltons directory (ie. that `project:init` would otherwise
     * generate into).
     *
     * For a top-level skelton entry marked as a Letterpress template (ie. its name contains the
     * `.lp` marker), the path is checked using its generated name (`.lp` marker removed), since
     * that is the name it would actually be written as.
     *
     * As a special case, an existing `app` directory that contains nothing but a `vendor`
     * directory (ie. only `composer install` has been run there, typically ahead of time by the
     * devcontainer setup) is not considered "already initialized".
     *
     * @param  string   $cwd
     * @return string[] absolute paths that already exist
     */
    protected function existingSkeltonEntries(string $cwd): array
    {
        $existing = [];
        foreach (scandir($this->skeltons_dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $name = Letterpress::isTemplateFile($item) ? Letterpress::stripMarker($item) : $item;
            $path = Path::normalize("{$cwd}/{$name}");
            if (!file_exists($path)) {
                continue;
            }
            if ($name === 'app' && is_dir($path) && $this->containsOnlyVendorDir($path)) {
                continue;
            }
            $existing[] = $path;
        }

        return $existing;
    }

    /**
     * Determine whether the given directory contains nothing but a `vendor` directory.
     *
     * @param  string $dir
     * @return bool
     */
    protected function containsOnlyVendorDir(string $dir): bool
    {
        $entries = array_values(array_diff(scandir($dir), ['.', '..']));
        return $entries === ['vendor'];
    }

    /**
     * Recursively generate the given destination directory from the given source (skeltons)
     * directory.
     *
     * Any file whose name contains the `.lp` marker (ex `application.lp.php`, `Dockerfile.lp`,
     * `.lp.env`) is rendered through Letterpress using the given $vars, and the `.lp` marker is
     * removed from the generated file name (ex `application.php`, `Dockerfile`, `.env`).
     * All other files are copied as-is. Directory structure (including empty directories) is
     * preserved, and each generated file keeps the permissions of its source file (so that, for
     * example, `app/bin/assistant` stays executable).
     *
     * If `$dry_run` is true, no directory/file is actually created/written (this method only
     * computes and returns the destination paths that would be generated).
     *
     * Any source path listed in `$exclude` (and everything under it, when it is a directory) is
     * skipped entirely.
     *
     * @param  string               $src_dir
     * @param  string               $dest_dir
     * @param  array<string, mixed> $vars
     * @param  bool                 $dry_run  (default: false)
     * @param  string[]             $exclude  absolute source paths to skip (default: [])
     * @return string[]             list of generated (or, when $dry_run, would-be-generated) file paths
     */
    protected function generate(string $src_dir, string $dest_dir, array $vars, bool $dry_run = false, array $exclude = []): array
    {
        if (!$dry_run && !is_dir($dest_dir)) {
            mkdir($dest_dir, 0o755, true);
        }

        $generated = [];
        foreach (scandir($src_dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $src = Path::normalize("{$src_dir}/{$item}");
            if (in_array($src, $exclude, true)) {
                continue;
            }

            if (is_dir($src)) {
                $generated = array_merge($generated, $this->generate($src, "{$dest_dir}/{$item}", $vars, $dry_run, $exclude));
                continue;
            }

            $is_template = Letterpress::isTemplateFile($item);
            $dest        = Path::normalize($dest_dir . '/' . ($is_template ? Letterpress::stripMarker($item) : $item));
            if (!$dry_run) {
                $content = file_get_contents($src);
                file_put_contents($dest, $is_template ? Letterpress::of($content)->with($vars)->render() : $content);
                chmod($dest, fileperms($src) & 0o777);
            }
            $generated[] = $dest;
        }

        return $generated;
    }

    /**
     * Get the `.devcontainer/docker/{driver}` skelton directories that should be excluded from
     * generation, ie. every database driver directory other than the one selected in $configs
     * (`database`), or all of them when the database is not used at all (`use_db` is false).
     *
     * @param  array<string, mixed> $configs
     * @return string[]             absolute source paths to exclude
     */
    protected function excludedDatabaseDirs(array $configs): array
    {
        $selected = ($configs['use_db'] ?? false) ? ($configs['database'] ?? null) : null;
        $excluded = array_filter(array_keys(static::SUPPORTED_DATABASES), fn($driver) => $driver !== $selected);

        return array_values(array_map(
            fn($driver) => Path::normalize("{$this->skeltons_dir}/.devcontainer/docker/{$driver}"),
            $excluded,
        ));
    }

    /**
     * Resolve the Composer package names to require from the given rules and the collected
     * $configs (see static::COMPOSER_REQUIRE / static::COMPOSER_REQUIRE_DEV).
     *
     * Each top-level key of $rules is either:
     *  - `'always'`, whose value is a plain list of package names that are always required
     *    regardless of $configs, or
     *  - a $configs key (ex `'view'`, `'cache'`), whose value is a map of
     *    `[$configs value => package name]`; the package is only required when
     *    `$configs[$group]` matches one of that map's keys.
     *
     * @param  array<string, array<int|string, string>> $rules
     * @param  array<string, mixed>                     $configs
     * @return string[]                                 unique package names
     */
    protected function resolveComposerPackages(array $rules, array $configs): array
    {
        $packages = [];
        foreach ($rules as $group => $mapping) {
            if ($group === 'always') {
                $packages = array_merge($packages, $mapping);
                continue;
            }

            $selected = $configs[$group] ?? null;
            if ($selected !== null && isset($mapping[$selected])) {
                $packages[] = $mapping[$selected];
            }
        }

        return array_values(array_unique($packages));
    }

    /**
     * Run `composer require` (or, when `$dev` is true, `composer require --dev`) for the given
     * packages against the `composer.json` in the given directory.
     *
     * @param  string   $cwd      project root directory (where `composer.json` lives)
     * @param  string[] $packages
     * @param  bool     $dev
     * @return bool     true on success (or when $packages is empty), false if the command failed
     */
    protected function composerRequire(string $cwd, array $packages, bool $dev): bool
    {
        if (empty($packages)) {
            return true;
        }

        $command = 'composer require ' . ($dev ? '--dev ' : '')
            . implode(' ', array_map('escapeshellarg', $packages))
            . ' --working-dir=' . escapeshellarg($cwd);

        $this->writeln("> {$command}");

        // Never actually shell out to Composer while under test (slow, network-dependent, and
        // would mutate the test working directory's vendor/composer.lock). RebetTestCase enables
        // System::testing() for every test, so this is transparent to callers/tests.
        // NOTE: passthru()'s $result_code is a by-reference parameter, which System::__callStatic()
        // cannot forward (PHP's magic __call/__callStatic always receives $args by value), so the
        // real passthru() must be called directly here rather than via System::passthru().
        if (System::testing()) {
            return true;
        }

        passthru($command, $exit_code);
        if ($exit_code !== 0) {
            $this->error('`composer require' . ($dev ? ' --dev' : '') . "` failed (exit code {$exit_code}).");
            return false;
        }

        return true;
    }
}
