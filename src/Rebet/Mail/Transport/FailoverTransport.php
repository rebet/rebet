<?php
namespace Rebet\Mail\Transport;

use Rebet\Mail\Mail;
use Swift_FailoverTransport;

/**
 * Load Balanced Transport class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class FailoverTransport extends Swift_FailoverTransport
{
    /**
     * Create Failover Transport using given transports configuration
     *
     * @param string[] $transports name configured in Mail.transports
     */
    public function __construct(array $transports)
    {
        parent::__construct(array_map(function ($transport) { return Mail::mailer($transport)->transport(); }, $transports));
    }
}
