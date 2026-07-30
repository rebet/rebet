<?php
namespace Rebet\Mail\Transport;

use Rebet\Mail\Mail;
use Swift_LoadBalancedTransport;

/**
 * Load Balanced Transport class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LoadBalancedTransport extends Swift_LoadBalancedTransport
{
    /**
     * Create Load Balanced Transport using given transports configuration
     *
     * @param string[] $transports name configured in Mail.transports
     */
    public function __construct(array $transports)
    {
        // Swiftmailer is abandoned and its constructor chain internally uses the
        // `call_user_func_array([$this, 'ParentClass::__construct'], ...)` idiom, which
        // is deprecated since PHP 8.2. The call itself still works correctly, so the
        // deprecation notice is suppressed here rather than patching the vendor library.
        @parent::__construct(array_map(function ($transport) { return Mail::mailer($transport)->transport(); }, $transports));
    }
}
