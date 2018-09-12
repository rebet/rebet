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
(
    [0] => 1
    [1] => 2
    [2] => 3
)

EOS
            ,$formatted
        );

        $formatted = $this->formatter->format($this->now, LogLevel::ERROR(), 'This is test message.', ['test' => 123]);
        $this->assertSame(<<<EOS
2010-10-20 10:20:30.040050 {$pid} [ERROR] This is test message.
>> *** CONTEXT ***
>> Array
>> (
>>     [test] => 123
>> )

EOS
            ,$formatted
        );

        $formatted = $this->formatter->format($this->now, LogLevel::ERROR(), 'This is test message.', ['test' => 123], new \LogicException("Test"));
        $this->assertStringStartsWith(<<<EOS
2010-10-20 10:20:30.040050 {$pid} [ERROR] This is test message.
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
        
        $formatted = $this->formatter->format($this->now, LogLevel::ERROR(), 'This is test message.', ['test' => 123], new \LogicException("Test"), ['etra' => 'abc']);
        $this->assertStringStartsWith(<<<EOS
2010-10-20 10:20:30.040050 {$pid} [ERROR] This is test message.
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
        $this->assertContains(<<<EOS
*** EXTRA ***
EOS
            ,$formatted
        );
    }
}