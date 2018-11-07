<?php
namespace Rebet\View\Engine\Smarty;

use Rebet\Config\Configurable;
use Rebet\View\Engine\Engine;

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
class Smarty implements Engine
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
            'customizers'  => [],
        ];
    }

    /**
     * Template file suffix
     *
     * @var string
     */
    protected $file_suffix = null;

    /**
     * The Smarty template engine.
     *
     * @var SmartyWrapper
     */
    protected $smarty = null;

    /**
     * Create Smarty template engine
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->file_suffix = $config['file_suffix'] ?? static::config('file_suffix') ;

        $this->smarty = new SmartyWrapper();
        $this->smarty->setTemplateDir($config['template_dir'] ?? static::config('template_dir'));
        $this->smarty->setCompileDir($config['compile_dir'] ?? static::config('compile_dir'));
        $this->smarty->setConfigDir($config['config_dir'] ?? static::config('config_dir', false));
        $this->smarty->setCacheDir($config['cache_dir'] ?? static::config('cache_dir', false));
        $this->smarty->setPluginsDir($config['plugins_dir'] ?? static::config('plugins_dir'));
        $this->smarty->escape_html = $config['escape_html'] ?? static::config('escape_html');

        $customizers = array_merge($config['customizers'] ?? [], static::config('customizers', false, []));
        foreach (array_reverse($customizers) as $customizer) {
            $invoker = \Closure::fromCallable($customizer);
            $invoker($this->smarty);
        }
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
        $this->smarty->source->exists = true;
        $this->smarty->assign($data);
        return $this->smarty->fetch($name.$this->file_suffix);
    }
}
