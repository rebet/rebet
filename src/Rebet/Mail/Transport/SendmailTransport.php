<?php
namespace Rebet\Mail\Transport;

use Rebet\Inflection\Inflector;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Reflection\Reflector;
use Swift_Plugins_AntiFloodPlugin;
use Swift_Plugins_BandwidthMonitorPlugin;
use Swift_Plugins_ImpersonatePlugin;
use Swift_Plugins_RedirectingPlugin;
use Swift_Plugins_ThrottlerPlugin;
use Swift_SendmailTransport;

/**
 * Sendmail Transport class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SendmailTransport extends Swift_SendmailTransport
{
    use PluginAccessible, PluginOptionable;

    /**
     * @param string $host (default: 'localhost')
     * @param int $port (default: 25)
     * @param string|null $username (default: null)
     * @param string|null $password (default: null)
     * @param array $options (default: [])
     *   - string       'source_ip'
     *   - string       'local_domain'
     *   - string       'sender'            : Use Swift_Plugins_ImpersonatePlugin [?]
     *   - array        'redirecting'       : Use Swift_Plugins_RedirectingPlugin ['recipient' => ?, 'whitelist' => []]
     *   - array        'antiflood'         : Use Swift_Plugins_AntiFloodPlugin ['threshold' => 99, 'sleep' => 0]
     *   - bool         'bandwidth_monitor' : Use Swift_Plugins_BandwidthMonitorPlugin [when value is true]
     *   - array        'throttle'          : Use Swift_Plugins_ThrottlerPlugin ['rate' => ?, 'mode' = Swift_Plugins_ThrottlerPlugin::BYTES_PER_MINUTE]
     *   - bool|string  'logging'           : Use LoggingPlugin [when value is true then use default channel, otherwise give channel name of Log.channels]
     *   - string|array 'always_bcc'        : Use AlwaysBccPlugin [?]
     * @param string|null $encryption null, 'tls' or 'ssl' (default: null for choose default by port number)
     */
    public function __construct(string $command = '/usr/sbin/sendmail -bs', array $options = [])
    {
        parent::__construct($command);
        foreach ($options as $option => $value) {
            if ($this->apply($option, $value)) {
                continue;
            }
            $setter = 'set'.Inflector::pascalize($option);
            if (method_exists($this, $setter)) {
                Reflector::invoke($this, $setter, [$value]);
                continue;
            }
            throw new LogicException("Invalid option '{$option}' was given.");
        }
    }
}
