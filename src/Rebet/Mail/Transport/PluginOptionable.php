<?php
namespace Rebet\Mail\Transport;

use Rebet\Mail\Plugins\AlwaysBccPlugin;
use Rebet\Mail\Plugins\LoggingPlugin;
use Rebet\Tools\Reflection\Reflector;
use Swift_Events_EventListener;
use Swift_Plugins_AntiFloodPlugin;
use Swift_Plugins_BandwidthMonitorPlugin;
use Swift_Plugins_ImpersonatePlugin;
use Swift_Plugins_RedirectingPlugin;
use Swift_Plugins_ThrottlerPlugin;

/**
 * Plugin Optionable Trait
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait PluginOptionable
{
    /**
     * Apply the given option if the option is available.
     *
     * @param string $option
     *   - string       'sender'            : Use Swift_Plugins_ImpersonatePlugin [?]
     *   - array        'redirecting'       : Use Swift_Plugins_RedirectingPlugin ['recipient' => ?, 'whitelist' => []]
     *   - array        'antiflood'         : Use Swift_Plugins_AntiFloodPlugin ['threshold' => 99, 'sleep' => 0]
     *   - bool         'bandwidth_monitor' : Use Swift_Plugins_BandwidthMonitorPlugin [when value is true]
     *   - array        'throttle'          : Use Swift_Plugins_ThrottlerPlugin ['rate' => ?, 'mode' = Swift_Plugins_ThrottlerPlugin::BYTES_PER_MINUTE]
     *   - bool|string  'logging'           : Use LoggingPlugin [when value is true then use default channel, otherwise give channel name of Log.channels]
     *   - string|array 'always_bcc'        : Use AlwaysBccPlugin [?]
     * @param mixed $value
     * @param array $unavailables option name (default: [] for all available)
     * @return bool applied or not
     */
    protected function apply(string $option, $value, array $unavailables = []) : bool
    {
        if (in_array($option, $unavailables)) {
            return false;
        }

        switch ($option) {
            case 'sender':            $this->registerPlugin(new Swift_Plugins_ImpersonatePlugin($value)); break;
            case 'redirecting':       $this->registerPlugin(Reflector::create(Swift_Plugins_RedirectingPlugin::class, $value)); break;
            case 'antiflood':         $this->registerPlugin(Reflector::create(Swift_Plugins_AntiFloodPlugin::class, $value)); break;
            case 'bandwidth_monitor': $value ? $this->registerPlugin(new Swift_Plugins_BandwidthMonitorPlugin()) : null; break;
            case 'throttle':          $this->registerPlugin(Reflector::create(Swift_Plugins_ThrottlerPlugin::class, $value)); break;
            case 'logging':           $this->registerPlugin(new LoggingPlugin(is_string($value) ? $value : null)); break;
            case 'always_bcc':        $this->registerPlugin(new AlwaysBccPlugin($value)); break;
            default: return false;
        }
        return true;
    }

    /**
     * Register a plugin in the Transport.
     *
     * @param Swift_Events_EventListener $plugin
     */
    abstract public function registerPlugin(Swift_Events_EventListener $plugin);
}
