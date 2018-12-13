<?php
namespace Rebet\Tests\Log\Formatter;

use Rebet\DateTime\DateTime;
use Rebet\Log\Formatter\DefaultFormatter;

use Rebet\Log\LogContext;
use Rebet\Log\LogLevel;
use Rebet\Tests\RebetTestCase;

class DefaultFormatterTest extends RebetTestCase
{
    private $context;
    private $formatter;

    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.040050');

        $this->context   = new LogContext(DateTime::now(), LogLevel::TRACE(), null);
        $this->formatter = new DefaultFormatter();
    }

    public function test_constract()
    {
        $this->assertInstanceOf(DefaultFormatter::class, new DefaultFormatter());
    }

    public function test_format()
    {
        $pid = getmypid();

        $this->context->level = LogLevel::TRACE();
        $formatted            = $this->formatter->format($this->context);
        $this->assertSame(
            <<<EOS
2010-10-20 10:20:30.040050 {$pid} [TRACE] 
EOS
            ,
            $formatted
        );
        
        $this->context->level   = LogLevel::DEBUG();
        $this->context->message = 123;
        $formatted              = $this->formatter->format($this->context);
        $this->assertStringStartsWith(
            <<<EOS
2010-10-20 10:20:30.040050 {$pid} [DEBUG] 123
EOS
            ,
            $formatted
        );
        
        $this->context->level   = LogLevel::INFO();
        $this->context->message = 'This is test message.';
        $formatted              = $this->formatter->format($this->context);
        $this->assertSame(
            <<<EOS
2010-10-20 10:20:30.040050 {$pid} [INFO ] This is test message.
EOS
            ,
            $formatted
        );
        
        $this->context->level   = LogLevel::INFO();
        $this->context->message = "1st line.\n2nd line.";
        $formatted              = $this->formatter->format($this->context);
        $this->assertSame(
            <<<EOS
2010-10-20 10:20:30.040050 {$pid} [INFO ] 1st line.
2nd line.
EOS
            ,
            $formatted
        );
        
        $this->context->level   = LogLevel::WARN();
        $this->context->message = [1, 2, 3];
        $formatted              = $this->formatter->format($this->context);
        $this->assertSame(
            <<<EOS
2010-10-20 10:20:30.040050 {$pid} [WARN ] Array
(
    [0] => 1
    [1] => 2
    [2] => 3
)
EOS
            ,
            $formatted
        );

        $this->context->level   = LogLevel::ERROR();
        $this->context->message = 'This is test message.';
        $this->context->var     = ['test' => 123];
        $formatted              = $this->formatter->format($this->context);
        $this->assertSame(
            <<<EOS
2010-10-20 10:20:30.040050 {$pid} [ERROR] This is test message.
== *** VAR ***
== Array
== (
==     [test] => 123
== )
EOS
            ,
            $formatted
        );

        $this->context->level   = LogLevel::FATAL();
        $this->context->message = 'This is test message.';
        $this->context->var     = ['test' => 123];
        $this->context->error   = new \LogicException("Test");
        $formatted              = $this->formatter->format($this->context);
        $this->assertStringStartsWith(
            <<<EOS
2010-10-20 10:20:30.040050 {$pid} [FATAL] This is test message.
EOS
            ,
            $formatted
        );
        $this->assertContains(
            <<<EOS
*** VAR ***
EOS
            ,
            $formatted
        );
        
        $this->context->level   = LogLevel::DEBUG();
        $this->context->message = 'This is test message.';
        $this->context->var     = ['test' => 123];
        $this->context->error   = new \LogicException("Test");
        $this->context->extra   = ['etra' => 'abc'];
        $formatted              = $this->formatter->format($this->context);
        $this->assertStringStartsWith(
            <<<EOS
2010-10-20 10:20:30.040050 {$pid} [DEBUG] This is test message.
EOS
            ,
            $formatted
        );
        $this->assertContains(
            <<<EOS
*** VAR ***
EOS
            ,
            $formatted
        );
        $this->assertContains(
            <<<EOS
*** EXTRA ***
EOS
            ,
            $formatted
        );
    }
}
