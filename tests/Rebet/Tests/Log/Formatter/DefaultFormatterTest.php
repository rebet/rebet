<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Log\Formatter\DefaultFormatter;
use Rebet\Log\LogLevel;
use Rebet\DateTime\DateTime;
use Rebet\Config\Config;
use Rebet\Config\App;

class DefaultFormatterTest extends RebetTestCase {

    private $now;
    private $formatter;

    public function setUp() {
        Config::clear();
        App::setTimezone('UTC');
        DateTime::setTestNow('2010-10-20 10:20:30.040050');

        $this->now       = DateTime::now();
        $this->formatter = DefaultFormatter::create();
    }

    public function test_create() {
        $this->assertInstanceOf(DefaultFormatter::class, DefaultFormatter::create());
    }

    public function test_format() {
        $pid = getmypid();

        $formatted = $this->formatter->format($this->now, LogLevel::TRACE(), null);
        $this->assertSame(<<<EOS
2010-10-20 10:20:30.040050 {$pid} [TRACE] 
EOS
            ,$formatted
        );
        
        $formatted = $this->formatter->format($this->now, LogLevel::DEBUG(), 123);
        $this->assertStringStartsWith(<<<EOS
2010-10-20 10:20:30.040050 {$pid} [DEBUG] 123
EOS
            ,$formatted
        );
        $this->assertContains(<<<EOS
*** DEBUG TRACE ***
EOS
            ,$formatted
        );
        
        $formatted = $this->formatter->format($this->now, LogLevel::INFO(), 'This is test message.');
        $this->assertSame(<<<EOS
2010-10-20 10:20:30.040050 {$pid} [INFO ] This is test message.
EOS
            ,$formatted
        );
        
        $formatted = $this->formatter->format($this->now, LogLevel::WARN(), [1, 2, 3]);
        $this->assertSame(<<<EOS
2010-10-20 10:20:30.040050 {$pid} [WARN ] Array
2010-10-20 10:20:30.040050 {$pid} [WARN ] (
2010-10-20 10:20:30.040050 {$pid} [WARN ]     [0] => 1
2010-10-20 10:20:30.040050 {$pid} [WARN ]     [1] => 2
2010-10-20 10:20:30.040050 {$pid} [WARN ]     [2] => 3
2010-10-20 10:20:30.040050 {$pid} [WARN ] )
EOS
            ,$formatted
        );

        $formatted = $this->formatter->format($this->now, LogLevel::ERROR(), 'This is test message.', ['test' => 123]);
        $this->assertSame(<<<EOS
2010-10-20 10:20:30.040050 {$pid} [ERROR] This is test message.
2010-10-20 10:20:30.040050 {$pid} [ERROR] == *** CONTEXT ***
2010-10-20 10:20:30.040050 {$pid} [ERROR] == Array
2010-10-20 10:20:30.040050 {$pid} [ERROR] == (
2010-10-20 10:20:30.040050 {$pid} [ERROR] ==     [test] => 123
2010-10-20 10:20:30.040050 {$pid} [ERROR] == )
EOS
            ,$formatted
        );

        $formatted = $this->formatter->format($this->now, LogLevel::FATAL(), 'This is test message.', ['test' => 123], new \LogicException("Test"));
        $this->assertStringStartsWith(<<<EOS
2010-10-20 10:20:30.040050 {$pid} [FATAL] This is test message.
EOS
            ,$formatted
        );
        $this->assertContains(<<<EOS
*** CONTEXT ***
EOS
            ,$formatted
        );
        $this->assertContains(<<<EOS
*** STACK TRACE ***
EOS
            ,$formatted
        );
        
        $formatted = $this->formatter->format($this->now, LogLevel::DEBUG(), 'This is test message.', ['test' => 123], new \LogicException("Test"), ['etra' => 'abc']);
        $this->assertStringStartsWith(<<<EOS
2010-10-20 10:20:30.040050 {$pid} [DEBUG] This is test message.
EOS
            ,$formatted
        );
        $this->assertContains(<<<EOS
*** CONTEXT ***
EOS
            ,$formatted
        );
        $this->assertContains(<<<EOS
*** DEBUG TRACE ***
EOS
            ,$formatted
        );
        $this->assertContains(<<<EOS
*** STACK TRACE ***
EOS
            ,$formatted
        );
        $this->assertContains(<<<EOS
*** EXTRA ***
EOS
            ,$formatted
        );
    }
}