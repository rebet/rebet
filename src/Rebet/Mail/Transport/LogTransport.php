<?php
namespace Rebet\Mail\Transport;

use Psr\Log\LoggerInterface;
use Rebet\Inflection\Inflector;
use Rebet\Log\Log;
use Rebet\Mail\Mime\MimeMessage;
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
 * Log Transport class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LogTransport extends AbstractTransport
{
    use PluginOptionable;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Create a new log transport.
     *
     * @param LoggerInterface|string|null $logger instance or log channel name configured in Log.channels (default: null for use default channel logger)
     * @param array $options (default: [])
     *   - string       'sender'            : Use Swift_Plugins_ImpersonatePlugin [?]
     *   - array        'redirecting'       : Use Swift_Plugins_RedirectingPlugin ['recipient' => ?, 'whitelist' => []]
     *   - array        'antiflood'         : Use Swift_Plugins_AntiFloodPlugin ['threshold' => 99, 'sleep' => 0]
     *   - bool         'bandwidth_monitor' : Use Swift_Plugins_BandwidthMonitorPlugin [when value is true]
     *   - array        'throttle'          : Use Swift_Plugins_ThrottlerPlugin ['rate' => ?, 'mode' = Swift_Plugins_ThrottlerPlugin::BYTES_PER_MINUTE]
     *   - string|array 'always_bcc'        : Use AlwaysBccPlugin [?]
     * @param Swift_Events_EventDispatcher|null $event_dispatcher (default: null for use Mail::container()->lookup('transport.eventdispatcher'))
     */
    public function __construct($logger = null, array $options = [], ?Swift_Events_EventDispatcher $event_dispatcher = null)
    {
        parent::__construct($event_dispatcher);
        $this->logger = $logger instanceof LoggerInterface ? $logger : Log::channel($logger)->driver() ;
        foreach ($options as $option => $value) {
            if ($this->apply($option, $value, ['logging'])) {
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

        $this->logger->debug(MimeMessage::convertToReadableString($message));
        $this->sendPerformed($message);

        return $this->countRecipients($message);
    }
}
