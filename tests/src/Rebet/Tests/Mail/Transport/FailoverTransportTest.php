<?php
namespace Rebet\Tests\Mail\Transport;

use Rebet\Mail\Transport\FailoverTransport;
use Rebet\Tests\RebetTestCase;

class FailoverTransportTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(FailoverTransport::class, new FailoverTransport(['test', 'log']));
    }
}
