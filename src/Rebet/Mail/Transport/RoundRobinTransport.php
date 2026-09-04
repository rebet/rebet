<?php
declare(strict_types=1);

namespace Rebet\Mail\Transport;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Rebet\Tools\Reflection\Reflector;
use Symfony\Component\Mailer\Transport\RoundRobinTransport as SymfonyRoundRobinTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Round-Robin Transport class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RoundRobinTransport extends SymfonyRoundRobinTransport
{
    /**
     * @param array<TransportInterface|class-string<TransportInterface>|array<string, mixed>> $transports
     * @param int $retry_period (default: 60)
     * @param LoggerInterface|null $logger (default: NullLogger)
     */
    public function __construct(
        array $transports,
        int $retry_period = 60,
        LoggerInterface|null $logger = new NullLogger(),
    ) {
        parent::__construct(array_map(fn ($transport) => Reflector::instantiate($transport), $transports), $retry_period, $logger);
    }
}
