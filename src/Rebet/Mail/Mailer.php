<?php
namespace Rebet\Mail;

use Swift_Events_EventListener;
use Swift_Mailer;
use Swift_Mime_SimpleMessage;
use Swift_Transport;

/**
 * Mailer Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Mailer
{
    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * Create a new Mailer using $transport for delivery.
     */
    public function __construct(Swift_Transport $transport)
    {
        $this->mailer = new Swift_Mailer($transport);
    }

    /**
     * Send the given Message like it would be sent in a mail client.
     *
     * @param Mail|Swift_Mime_SimpleMessage $message
     * @return array of failed recipients
     */
    public function send($mail) : array
    {
        $failed_recipients = [];
        $this->mailer->send($mail instanceof Mail ? $mail->message() : $mail, $failed_recipients);
        return $failed_recipients;
    }

    /**
     * Register a plugin using a known unique key (e.g. myPlugin).
     */
    public function plugin(Swift_Events_EventListener $plugin)
    {
        $this->mailer->registerPlugin($plugin);
    }

    /**
     * The Transport used to send messages.
     *
     * @return Swift_Transport
     */
    public function transport() : Swift_Transport
    {
        return $this->mailer->getTransport();
    }
}
