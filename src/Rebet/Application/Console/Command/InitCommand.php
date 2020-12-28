<?php
namespace Rebet\Application\Console\Command;

use Rebet\Console\Command\Command;
use Rebet\Tools\Utility\Path;
use Rebet\Tools\Utility\Strings;
use Symfony\Component\Console\Input\InputArgument;

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
        ['locale'       , 'l' , InputArgument::OPTIONAL, 'Default application locale.'],
        ['timezone'     , 'tz', InputArgument::OPTIONAL, 'Default application timezone.'],
        ['database'     , 'd' , InputArgument::OPTIONAL, 'Database product.'],
        ['database-name', 'dn', InputArgument::OPTIONAL, 'Database name for local development.'],
        ['database-user', 'du', InputArgument::OPTIONAL, 'Database user for local development.'],
        ['database-pass', 'dp', InputArgument::OPTIONAL, 'Database password for local development.'],
        ['view'         , 've', InputArgument::OPTIONAL, 'View template engine.'],
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
        $this->writeln("1) Setup Your Application Configs (1/{$total_step})");
        $configs['locale']   = $this->ask("* Default Locale   : ", 'locale') ;
        $configs['timezone'] = $this->ask("* Default Timezone : ", 'timezone') ;

        $this->writeln('');
        $this->writeln("2) Setup Database For Local Development Configs (2/{$total_step})");
        $use_db = false;
        if ($this->option('database') || $this->confirm("Will you use database? [y/n] : ")) {
            $configs['database'] = $this->choice("* DB Product  : ", [
                1 => 'sqlite',
                2 => 'mysql',
                3 => 'pgsql'
            ], 'database');
            $is_sqlite           = $configs['database'] === 'sqlite';
            $default_db_name     = $is_sqlite ? "{$app}.db" : $app ;
            $configs['db_name']  = $this->ask("* DB Name     : [{$default_db_name}] ", 'database-name', true, $default_db_name);
            if (!$is_sqlite) {
                $configs['db_user']  = $this->ask("* DB User     : [{$app}] ", 'database-user', true, $app);
                $configs['db_pass']  = $this->ask("* DB Password : [P@ssw0rd] ", 'database-pass', true, 'P@ssw0rd');
            }
            $use_db = true;
        }

        $this->writeln('');
        $this->writeln("3) Setup View Configs (3/{$total_step})");
        $configs['view'] = $this->choice("* View Engine : ", [
            1 => 'Twig',
            2 => 'Larabel Blade',
        ], 'view');


        $this->writeln('');
        $this->comment('You are inputed -------');
        $this->comment(Strings::stringify($configs));
        $this->comment('-----------------------');

        $this->comment('Application ready! Build something amazing.');
    }
}
