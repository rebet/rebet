<?php
namespace Rebet\Tests\Mail\Transport;

use Rebet\Mail\Transport\LoadBalancedTransport;
use Rebet\Tests\RebetTestCase;

class LoadBalancedTransportTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(LoadBalancedTransport::class, new LoadBalancedTransport(['test', 'log']));
    }
}
