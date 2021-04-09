<?php
namespace Rebet\Mail\Transport;

use Rebet\Inflection\Inflector;
use Rebet\Mail\Plugins\AlwaysBccPlugin;
use Rebet\Mail\Plugins\LoggingPlugin;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Reflection\Reflector;
use Swift_Events_EventDispatcher;
use Swift_Mime_SimpleMessage;
use Swift_Plugins_AntiFloodPlugin;
use Swift_Plugins_BandwidthMonitorPlugin;
use Swift_Plugins_ImpersonatePlugin;
use Swift_Plugins_RedirectingPlugin;
use Swift_Plugins_ThrottlerPlugin;

/**
 * Array Transport class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ArrayTransport extends AbstractTransport
{
    use PluginOptionable;

    /**
     * Sent messages.
     *
     * @var Swift_Mime_SimpleMessage[]
     */
    protected $messages = [];

    /**
     * Create a new array transport.
     *
     * @param array $options (default: [])
     *   - string       'sender'            : Use Swift_Plugins_ImpersonatePlugin [?]
     *   - array        'redirecting'       : Use Swift_Plugins_RedirectingPlugin ['recipient' => ?, 'whitelist' => []]
     *   - array        'antiflood'         : Use Swift_Plugins_AntiFloodPlugin ['threshold' => 99, 'sleep' => 0]
     *   - bool         'bandwidth_monitor' : Use Swift_Plugins_BandwidthMonitorPlugin [when value is true]
     *   - array        'throttle'          : Use Swift_Plugins_ThrottlerPlugin ['rate' => ?, 'mode' = Swift_Plugins_ThrottlerPlugin::BYTES_PER_MINUTE]
     *   - bool|string  'logging'           : Use LoggingPlugin [when value is true then use default channel, otherwise give channel name of Log.channels]
     *   - string|array 'always_bcc'        : Use AlwaysBccPlugin [?]
     * @param Swift_Events_EventDispatcher|null $event_dispatcher (default: null for use Mail::container()->lookup('transport.eventdispatcher'))
     */
    public function __construct(array $options = [], ?Swift_Events_EventDispatcher $event_dispatcher = null)
    {
        parent::__construct($event_dispatcher);
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

    /**
     * {@inheritDoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        if (!$this->beforeSendPerformed($message)) {
            return 0;
        }

        $this->messages[] = $message;
        $this->sendPerformed($message);

        return $this->countRecipients($message);
    }

    /**
     * Get sent messages.
     *
     * @return Swift_Mime_SimpleMessage[]
     */
    public function messages() : array
    {
        return $this->messages;
    }
}
