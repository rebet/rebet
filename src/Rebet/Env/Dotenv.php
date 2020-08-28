<?php
namespace Rebet\Env;

use Dotenv\Dotenv as VlucasDotenv;

/**
 * Dotenv Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Dotenv extends VlucasDotenv
{
    /**
     * Initialize the Dotenv module and load the .env file.
     * Note: This method is supposed to be called immediately after composer ../vendor/autoload.php.
     *
     * @param string $path of .env file
     * @param string $filename of .env file (default: .env)
     * @param bool $overload (default: true)
     * @return Dotenv
     */
    public static function init(string $path, string $filename = '.env', bool $overload = true) : self
    {
        $dotenv = new static($path, $filename);
        if ($overload) {
            $dotenv->overload();
        } else {
            $dotenv->load();
        }
        return $dotenv;
    }
}
