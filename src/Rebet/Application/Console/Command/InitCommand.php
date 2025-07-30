<?php
namespace Rebet\Application\Console\Command;

use Rebet\Auth\Password;
use Rebet\Console\Command\Command;
use Rebet\Inflection\Inflector;
use Rebet\Tools\Utility\Path;
use Rebet\Tools\Utility\Strings;
use Symfony\Component\Console\Input\InputOption;

/**
 * Init Command Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class InitCommand extends Command
{
    const NAME        = 'init';
    const DESCRIPTION = 'Initialize a new Rebet application';
    const OPTIONS     = [
        ['domain'        , 'd'  , InputOption::VALUE_OPTIONAL, 'Application domain for local development.'],
        ['locale'        , 'l'  , InputOption::VALUE_OPTIONAL, 'Default application locale.'],
        ['timezone'      , 'tz' , InputOption::VALUE_OPTIONAL, 'Default application timezone.'],
        ['database'      , 'db' , InputOption::VALUE_OPTIONAL, 'Database product.'],
        ['database-name' , 'dbn', InputOption::VALUE_OPTIONAL, 'Database name for local development.'],
        ['database-user' , 'dbu', InputOption::VALUE_OPTIONAL, 'Database user for local development.'],
        ['database-pass' , 'dbp', InputOption::VALUE_OPTIONAL, 'Database password for local development.'],
        ['auth'          , 'a'  , InputOption::VALUE_NONE    , 'Use user auth (when do not use database then use ArrayProvider as read only authentication)'],
        ['view'          , 've' , InputOption::VALUE_OPTIONAL, 'View template engine.'],
        ['cache'         , 'c'  , InputOption::VALUE_OPTIONAL, 'Cache store product.'],
        ['memcached-user', 'mu' , InputOption::VALUE_OPTIONAL, 'Memcached user for local development.'],
        ['memcached-pass', 'mp' , InputOption::VALUE_OPTIONAL, 'Memcached password for local development.'],
        ['http-port'     , 'p'  , InputOption::VALUE_OPTIONAL, 'Nginx http port number for local development.'],
        ['https-port'    , 'sp' , InputOption::VALUE_OPTIONAL, 'Nginx https port number for local development.'],
    ];

    protected function handle()
    {
        // @todo https://symfony.com/doc/current/console.html
        // @todo https://symfony.com/doc/current/components/console/helpers/questionhelper.html
        // @todo https://techblog.istyle.co.jp/archives/97
        // @todo https://github.com/laravel/framework/blob/7.x/src/Illuminate/Foundation/Console/EnvironmentCommand.php

        $total_step = 7;
        $step       = 0;

        $configs['cwd']       = $cwd = Path::normalize(getcwd());

        $this->comment('===========================================');
        $this->comment(' Welcome to Rebet Application Initializing ');
        $this->comment('===========================================');
        $this->comment('Please answer questions below.');


        $step++;
        $this->writeln('');
        $this->writeln("{$step}) Setup Your Application Default Configs ({$step}/{$total_step})");
        $configs['code_name'] = $code_name = $this->ask("* Application Code Name : ", null, true, Inflector::kebabize(basename($cwd))) ;
        $configs['locale']    = $this->ask("* Default Locale        : ", 'locale') ;
        $configs['timezone']  = $this->ask("* Default Timezone      : ", 'timezone') ;


        $step++;
        $this->writeln('');
        $this->writeln("{$step}) Setup Your Application Domain for Local Development ({$step}/{$total_step})");
        $this->comment(" - If you already have production domain, then type it with prefix `local.` (ex local.{$code_name}.com)");
        $this->comment(" - If you don't have production domain yet, then type app name with suffix `.local` (ex {$code_name}.local)");
        $this->comment(" - If you don't care local development doamin, then type `localhost`");
        $configs['domain'] = $domain = $this->ask("* Application Domain for Local Development : ", 'domain', true);


        $step++;
        $this->writeln('');
        $this->writeln("{$step}) Setup Database For Local Development Configs ({$step}/{$total_step})");
        $use_db    = false;
        $is_sqlite = false;
        if ($this->option('database') || $this->confirm("Will you use database? [y/n] : ")) {
            $configs['database'] = $this->choice("* DB Product  : ", [
                'sqlite'  => 'SQLite 3',
                'mysql'   => 'MySQL',
                'mariadb' => 'MariaDB',
                'pgsql'   => 'PostgreSQL',
            ], 'database');
            $is_sqlite          = $configs['database'] === 'sqlite';
            $configs['db_name'] = $this->ask("* DB Name     : [{$code_name}] ", 'database-name', true, $code_name);
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


        $step++;
        $this->writeln('');
        $this->writeln("{$step}) Setup Auth Configs ({$step}/{$total_step})");
        $use_auth = false;
        if ($this->option('auth') || $this->confirm("Will you use user auth? [y/n] : ")) {
            if (!$use_db) {
                $this->comment("You do not use database, so set ArrayProvider as read only authentication.");
                $this->comment("Please input an authentication user information that will be written in auth.php configuration file.");
                $this->writeln("NOTE: If you want to change the password or add new user then you can use Rebet assistant `hash:password` command to create password hash.");
                $configs['auth_name']     = $this->ask("* Name             : ", null, true);
                $configs['auth_email']    = $this->ask("* Email            : ", null, true);
                $configs['auth_password'] = Password::hash($this->password("* Password         : ", "* Confirm Password : "));
            }
            $use_auth = true;
        }
        $configs['use_auth'] = $use_auth;


        $step++;
        $this->writeln('');
        $this->writeln("{$step}) Setup View Configs ({$step}/{$total_step})");
        $configs['view'] = $this->choice("* View Engine : ", [
            'twig'  => 'Twig',
            'blade' => 'Larabel Blade',
        ], 'view');


        $step++;
        $this->writeln('');
        $this->writeln("{$step}) Setup Cache Store For Local Development Configs ({$step}/{$total_step})");
        $use_cache = false;
        if ($this->option('cache') || $this->confirm("Will you use cache store? [y/n] : ")) {
            $configs['cache'] = $this->choice(
                "* Cache Store : ",
                [
                    'apcu'      => 'APCu',
                ] +
                ($use_db ? ['database'  => 'Database'] : []) +
                [
                    'file'      => 'File System',
                    'memcached' => 'Memcached',
                    'redis'     => 'Redis',
                ],
                'cache'
            );
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


        $step++;
        $this->writeln('');
        $this->writeln("{$step}) Setup Nginx For Local Development Configs ({$step}/{$total_step})");
        $configs['http_port']  = $this->ask("* HTTP  Port : [80] ", 'http-port', true, '80');
        $configs['https_port'] = $https_port = $this->ask("* HTTPS Port : [443] ", 'https-port', true, '443');

        $this->writeln('');
        $this->comment('You are inputed -------');
        $this->comment(Strings::stringify($configs));
        $this->comment('-----------------------');

        $app_url = 'https://'.$domain.($https_port == '443' ? '' : ":{$https_port}");
        $this->info('-----------------------');
        $this->info("Let's add `127.0.0.1 {$domain}` to your hosts file.");
        $this->info("Then access:");
        $this->info(" - Site Top : {$app_url}");
        if($use_db && !$is_sqlite) {
            $this->info(" - Adminer  : {$app_url}/adminer/");
        }
        $this->info('-----------------------');
        
        $this->comment('Application ready! Build something amazing.');
    }
}
