<?php
namespace Rebet\View\Engine\Smarty;

use Rebet\Config\Configurable;
use Rebet\View\Engine\Engine;
use Rebet\File\Files;

/**
 * Smarty templeate engine Class
 *
 * This class depends on smarty/smarty ^3.1, so you should run composer command like below.
 * $ composer require smarty/smarty
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Smarty extends \Smarty implements Engine
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'template_dir' => null,
            'compile_dir'  => null,
            'config_dir'   => null,
            'cache_dir'    => null,
            'plugins_dir'  => ['plugins'],
            'file_suffix'  => '.tpl',
            'escape_html'  => true,
        ];
    }

    /**
     * Template file suffix
     *
     * @var string
     */
    protected $file_suffix = null;

    /**
     * Create Smarty template engine
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct();

        $this->file_suffix = $config['file_suffix'] ?? static::config('file_suffix') ;
        $this->setTemplateDir($config['template_dir'] ?? static::config('template_dir'));
        $this->setCompileDir($config['compile_dir'] ?? static::config('compile_dir'));
        $this->setConfigDir($config['config_dir'] ?? static::config('config_dir', false));
        $this->setCacheDir($config['cache_dir'] ?? static::config('cache_dir', false));
        $this->setPluginsDir($config['plugins_dir'] ?? static::config('plugins_dir'));
        $this->escape_html = $config['escape_html'] ?? static::config('escape_html');
    }

    /**
     * Get the string contents of the view.
     *
     * @param string $name Template name without base template dir and template file suffix
     * @param array $data
     * @return string
     */
    public function render(string $name, array $data = []) : string
    {
        $this->assign($data);
        return $this->fetch($name.$this->file_suffix);
    }

    /**
     * Normalize path
     *  - remove /./ and /../
     *  - make it absolute if required
     *  - replace \ to /
     *
     * @param string $path     file path
     * @param bool   $realpath if true - convert to absolute
     *                         false - convert to relative
     *                         null - keep as it is but
     *                         remove /./ /../
     *
     * @return string
     */
    public function _realpath($path, $realpath = null)
    {
        preg_match(
            '%^(?<root>(?:[[:alpha:]]:[\\\\/]|/|[\\\\]{2}[[:alpha:]]+|[[:print:]]{2,}:[/]{2}|[\\\\])?)(?<path>(.*))$%u',
            $path,
            $parts
        );
        $path = $parts[ 'path' ];
        if ($parts[ 'root' ] === '\\') {
            $parts[ 'root' ] = substr(getcwd(), 0, 2) . $parts[ 'root' ];
        } else {
            if ($realpath !== null && !$parts[ 'root' ]) {
                $path = getcwd() . '/' . $path;
            }
        }
        // normalize '/'
        $path = str_replace('\\', '/', $path);
        $parts[ 'root' ] = str_replace('\\', '/', $parts[ 'root' ]);
        do {
            $path = preg_replace(
                array('#[\\\\/]{2}#', '#[\\\\/][.][\\\\/]#', '#[\\\\/]([^\\\\/.]+)[\\\\/][.][.][\\\\/]#'),
                '/',
                $path,
                -1,
                $count
            );
        } while ($count > 0);
        return $realpath !== false ? $parts[ 'root' ] . $path : str_ireplace(getcwd(), '.', $parts[ 'root' ] . $path);
    }
}
