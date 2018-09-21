<?php
namespace Rebet\Tests\Log;

use Rebet\Tests\RebetTestCase;

use Rebet\Log\Log;

use Rebet\DateTime\DateTime;
use Rebet\Config\App;
use Rebet\Config\Config;
use Rebet\Log\LogLevel;

class LogTest extends RebetTestCase
{
    public function setUp()
    {
        Config::clear();
        App::setTimezone('UTC');
        DateTime::setTestNow('2010-10-20 10:20:30.040050');
    }
    
    public function test_log()
    {
        // $this->assertSameStderr('', function () {
        //     Log::trace('Test');
        // });
    }
}
