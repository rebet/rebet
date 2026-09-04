<?php
declare(strict_types=1);

namespace Rebet\Mail\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * In-Memory Transport class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class InMemoryTransport extends AbstractTransport
{
    /**
     * The sent message.
     *
     * @var SentMessage|null
     */
    private SentMessage|null $message = null;

    /**
     * Create InMemoryTransport instance.
     *
     * @param EventDispatcherInterface|null $dispatcher (default: null)
     * @param LoggerInterface|null $logger (default: null)
     */
    public function __construct(
        EventDispatcherInterface|null $dispatcher = null,
        LoggerInterface|null $logger = null,
    ) {
        parent::__construct($dispatcher, $logger);
    }

    /**
     * {@inheritDoc}
     */
    protected function doSend(SentMessage $message) : void
    {
        $this->message = $message;
    }

    /**
     * Get the latest sent message.
     *
     * @return SentMessage|null
     */
    public function getSentMessage() : SentMessage|null
    {
        return $this->message;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString() : string
    {
        return 'rebet://in-memory';
    }
}
