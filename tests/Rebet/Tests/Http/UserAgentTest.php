<?php
namespace Rebet\Tests\Http;

use DeviceDetector\DeviceDetector;
use Rebet\Http\UserAgent;
use Rebet\Tests\RebetTestCase;

class UserAgentTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(UserAgent::class, new UserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36'));
        $this->assertInstanceOf(UserAgent::class, new UserAgent(new DeviceDetector('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36')));
    }

    public function test_valueOf()
    {
        $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36';
        $this->assertNull(UserAgent::valueOf(null));
        $this->assertNull(UserAgent::valueOf(123));
        $this->assertInstanceOf(UserAgent::class, UserAgent::valueOf($ua));
        $this->assertInstanceOf(UserAgent::class, UserAgent::valueOf(new DeviceDetector($ua)));
        $this->assertInstanceOf(UserAgent::class, UserAgent::valueOf(new UserAgent($ua)));
    }

    public function test___toString()
    {
        $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36';
        $this->assertSame($ua, (new UserAgent($ua))->__toString());
    }
}
