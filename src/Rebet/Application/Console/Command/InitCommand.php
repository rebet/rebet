<?php
namespace Rebet\Application\Console\Command;

use Rebet\Auth\Password;
use Rebet\Console\Command\Command;
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
        ['locale'       , 'l' , InputOption::VALUE_OPTIONAL, 'Default application locale.'],
        ['timezone'     , 'tz', InputOption::VALUE_OPTIONAL, 'Default application timezone.'],
        ['database'     , 'd' , InputOption::VALUE_OPTIONAL, 'Database product.'],
        ['database-name', 'dn', InputOption::VALUE_OPTIONAL, 'Database name for local development.'],
        ['database-user', 'du', InputOption::VALUE_OPTIONAL, 'Database user for local development.'],
        ['database-pass', 'dp', InputOption::VALUE_OPTIONAL, 'Database password for local development.'],
        ['auth'         , 'a' , InputOption::VALUE_NONE    , 'Use user auth (when do not use database then use ArrayProvider as read only authentication)'],
        ['view'         , 've', InputOption::VALUE_OPTIONAL, 'View template engine.'],
    ];

    protected function handle()
    {
        // @todo https://symfony.com/doc/current/console.html
        // @todo https://symfony.com/doc/current/components/console/helpers/questionhelper.html
        // @todo https://techblog.istyle.co.jp/archives/97
        // @todo https://github.com/laravel/framework/blob/7.x/src/Illuminate/Foundation/Console/EnvironmentCommand.php

        $total_step = 4;

        $configs['cwd'] = $cwd = Path::normalize(getcwd());
        $configs['app'] = $app = basename($cwd);

        $this->comment('===========================================');
        $this->comment(' Welcome to Rebet Application Initializing ');
        $this->comment('===========================================');
        $this->comment('Please answer questions below.');


        $this->writeln('');
        $this->writeln("1) Setup Your Application Default Configs (1/{$total_step})");
        $configs['locale']   = $this->ask("* Default Locale   : ", 'locale') ;
        $configs['timezone'] = $this->ask("* Default Timezone : ", 'timezone') ;


        $this->writeln('');
        $this->writeln("2) Setup Database For Local Development Configs (2/{$total_step})");
        $use_db = false;
        if ($this->option('database') || $this->confirm("Will you use database? [y/n] : ")) {
            $configs['database'] = $this->choice("* DB Product  : ", [
                'sqlite'  => 'SQLite 3',
                'mysql'   => 'MySQL',
                'mariadb' => 'MariaDB',
                'pgsql'   => 'PostgreSQL',
                'sqlsrv'  => 'Microsoft SQL Server',
            ], 'database');
            $is_sqlite          = $configs['database'] === 'sqlite';
            $default_db_name    = $is_sqlite ? "{$app}.db" : $app ;
            $configs['db_name'] = $this->ask("* DB Name     : [{$default_db_name}] ", 'database-name', true, $default_db_name);
            if (!$is_sqlite) {
                $configs['db_user'] = $this->ask("* DB User     : [{$app}] ", 'database-user', true, $app);
                $configs['db_pass'] = $this->ask("* DB Password : [P@ssw0rd] ", 'database-pass', true, 'P@ssw0rd');
            }
            $use_db = true;
        }
        $configs['use_db'] = $use_db;


        $this->writeln('');
        $this->writeln("3) Setup Auth Configs (3/{$total_step})");
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


        $this->writeln('');
        $this->writeln("4) Setup View Configs (4/{$total_step})");
        $configs['view'] = $this->choice("* View Engine : ", [
            'twig'  => 'Twig',
            'blade' => 'Larabel Blade',
        ], 'view');


        $this->writeln('');
        $this->comment('You are inputed -------');
        $this->comment(Strings::stringify($configs));
        $this->comment('-----------------------');

        $this->comment('Application ready! Build something amazing.');
    }
}
