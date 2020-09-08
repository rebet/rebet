<?php
namespace Rebet\Env;

use Dotenv\Dotenv as VlucasDotenv;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;

/**
 * Dotenv Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Dotenv
{
    /**
     * Initialize the Dotenv module and load the .env file.
     * Note: This method is supposed to be called immediately after composer ../vendor/autoload.php.
     *
     * @param string|string[] $paths of .env file
     * @param string|string[] $names of .env file (default: '.env')
     * @param bool $overload (default: true)
     * @return array
     */
    public static function load($paths, $names = '.env', bool $overload = true) : void
    {
        $builder = RepositoryBuilder::createWithDefaultAdapters()->addWriter(PutenvAdapter::class);
        $dotenv  = VlucasDotenv::create($overload ? $builder->make() : $builder->immutable()->make(), $paths, $names);
        $dotenv->load();
    }
}
