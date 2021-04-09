<?php
namespace Rebet\Mail\Plugins;

use Rebet\Log\Log;
use Rebet\Mail\Mime\MimeMessage;
use Swift_Events_SendEvent;
use Swift_Events_SendListener;

/**
 * Logging Plugin class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LoggingPlugin implements Swift_Events_SendListener
{
    /**
     * Log channel name
     *
     * @var string|null
     */
    protected $channel;

    /**
     * Create Logging plugin
     *
     * @param string|null $channel (default: null for use default channel)
     */
    public function __construct(?string $channel = null)
    {
        $this->channel = $channel;
    }

    /**
     * Invoked immediately before the Message is sent.
     *
     * @param Swift_Events_SendEvent $evt
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $evt)
    {
        // Nothing to do
    }

    /**
     * Invoked immediately after the Message is sent.
     *
     * @param Swift_Events_SendEvent $evt
     */
    public function sendPerformed(Swift_Events_SendEvent $evt)
    {
        Log::channel($this->channel)->debug(MimeMessage::convertToReadableString($evt->getMessage()));
    }
}
