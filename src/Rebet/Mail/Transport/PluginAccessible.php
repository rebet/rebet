<?php
namespace Rebet\Mail\Transport;

use Swift_Events_EventListener;

/**
 * Plugin Accessible Trait
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait PluginAccessible
{
    /**
     * Registered plugins
     *
     * @var Swift_Events_EventListener[]
     */
    protected $plugins = [];

    /**
     * {@inheritDoc}
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        foreach ($this->plugins as $p) {
            // Already loaded (Same as Swift_Events_SimpleEventDispatcher::bindEventListener() check logic)
            if ($p === $plugin) {
                return;
            }
        }
        $this->plugins[] = $plugin;
        parent::registerPlugin($plugin);
    }

    /**
     * Get registered plugins.
     *
     * @return Swift_Events_EventListener[]
     */
    public function plugins() : array
    {
        return $this->plugins;
    }
}
