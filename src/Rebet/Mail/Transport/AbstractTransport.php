<?php
namespace Rebet\Mail\Transport;

use Rebet\Mail\Mail;
use Swift_Events_EventDispatcher;
use Swift_Events_EventListener;
use Swift_Events_SendEvent;
use Swift_Mime_SimpleMessage;
use Swift_Transport;

/**
 * Abstract Transport class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class AbstractTransport implements Swift_Transport
{
    /**
     * Sending messages.
     *
     * @var Swift_Mime_SimpleMessage[]
     */
    protected $messages = [];

    /**
     * The event dispatcher from the plugin API
     *
     * @var Swift_Events_EventDispatcher
     */
    protected $event_dispatcher;

    /**
     * Registered plugins
     *
     * @var Swift_Events_EventListener[]
     */
    protected $plugins = [];

    /**
     * Constructor
     *
     * @param Swift_Events_EventDispatcher|null $event_dispatcher (default: null for use Mail::container()->lookup('transport.eventdispatcher'))
     */
    public function __construct(?Swift_Events_EventDispatcher $event_dispatcher = null)
    {
        $this->event_dispatcher = $event_dispatcher ?? Mail::container()->lookup('transport.eventdispatcher');
    }

    /**
     * Tests if this Transport mechanism has started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * Starts this Transport mechanism.
     */
    public function start()
    {
    }

    /**
     * Stops this Transport mechanism.
     */
    public function stop()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function ping()
    {
        return true;
    }

    /**
     * Proccess beforeSendPerformed event.
     *
     * @param Swift_Mime_SimpleMessage $message
     * @return bool
     */
    protected function beforeSendPerformed(Swift_Mime_SimpleMessage $message) : bool
    {
        if ($evt = $this->event_dispatcher->createSendEvent($this, $message)) {
            $this->event_dispatcher->dispatchEvent($evt, 'beforeSendPerformed');
            if ($evt->bubbleCancelled()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Proccess sendPerformed event.
     *
     * @param Swift_Mime_SimpleMessage $message
     * @param int $result Swift_Events_SendEvent::RESULT_* (default: Swift_Events_SendEvent::RESULT_SUCCESS)
     * @return bool
     */
    protected function sendPerformed(Swift_Mime_SimpleMessage $message, int $result = Swift_Events_SendEvent::RESULT_SUCCESS) : bool
    {
        if ($evt = $this->event_dispatcher->createSendEvent($this, $message)) {
            $evt->setResult($result);
            $this->event_dispatcher->dispatchEvent($evt, 'sendPerformed');
        }

        return true;
    }

    /**
     * Get the count of recipients.
     *
     * @param  \Swift_Mime_SimpleMessage  $message
     * @return int
     */
    protected function countRecipients(Swift_Mime_SimpleMessage $message)
    {
        return count(array_merge(
            (array) $message->getTo(),
            (array) $message->getCc(),
            (array) $message->getBcc()
        ));
    }

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
        $this->event_dispatcher->bindEventListener($plugin);
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
