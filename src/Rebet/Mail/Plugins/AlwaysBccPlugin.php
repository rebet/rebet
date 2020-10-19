<?php
namespace Rebet\Mail\Plugins;

use Rebet\Mail\Mail;
use Swift_Events_SendEvent;
use Swift_Events_SendListener;

/**
 * Always Bcc Plugin class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class AlwaysBccPlugin implements Swift_Events_SendListener
{
    /**
     * @var array of bcc addresses to send always
     */
    protected $bccs = [];

    /**
     * Create Always Bcc Plugin
     *
     * @param string|array $bccs can be 'foo@bar.com', 'Foo <foo@bar.com>', ['foo@bar.com' => 'Foo'] or ['foo@bar.com' => 'Foo', 'baz@bar.com', 'Qux <qux@bar.com>', ...]
     */
    public function __construct($bccs)
    {
        $this->bccs = Mail::resolve($bccs);
    }

    /**
     * Invoked immediately before the Message is sent.
     *
     * @param Swift_Events_SendEvent $evt
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $evt)
    {
        foreach ($this->bccs as $address => $name) {
            $evt->getMessage()->addBcc($address, $name);
        }
    }

    /**
     * Invoked immediately after the Message is sent.
     *
     * @param Swift_Events_SendEvent $evt
     */
    public function sendPerformed(Swift_Events_SendEvent $evt)
    {
        // Nothing to do
    }

    /**
     * Get bcc addresses to send always.
     *
     * @return array
     */
    public function bccs() : array
    {
        return $this->bccs;
    }
}
