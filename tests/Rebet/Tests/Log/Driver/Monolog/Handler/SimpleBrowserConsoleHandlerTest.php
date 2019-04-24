<?php
namespace Rebet\Tests\Log\Driver\Monolog\Handler;

use Monolog\Logger as MonologLogger;
use Rebet\DateTime\DateTime;
use Rebet\Log\Driver\Monolog\Handler\SimpleBrowserConsoleHandler;
use Rebet\Tests\RebetTestCase;

class SimpleBrowserConsoleHandlerTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.123456');
    }

    public function test___construct()
    {
        $this->assertInstanceOf(SimpleBrowserConsoleHandler::class, new SimpleBrowserConsoleHandler());
    }

    public function dataSends() : array
    {
        $pid = getmypid();
        return [
            [["c.log", "%c2010-10-20 10:20:30.123456 web/{$pid} [DEBUG] Log Message."    , SimpleBrowserConsoleHandler::OUTPUT_STYLES[MonologLogger::DEBUG][1]]    , ['level' => MonologLogger::DEBUG]],
            [["c.log", "%c2010-10-20 10:20:30.123456 web/{$pid} [INFO] Log Message."     , SimpleBrowserConsoleHandler::OUTPUT_STYLES[MonologLogger::INFO][1]]     , ['level' => MonologLogger::INFO]],
            [["c.log", "%c2010-10-20 10:20:30.123456 web/{$pid} [NOTICE] Log Message."   , SimpleBrowserConsoleHandler::OUTPUT_STYLES[MonologLogger::NOTICE][1]]   , ['level' => MonologLogger::NOTICE]],
            [["c.log", "%c2010-10-20 10:20:30.123456 web/{$pid} [WARNING] Log Message."  , SimpleBrowserConsoleHandler::OUTPUT_STYLES[MonologLogger::WARNING][1]]  , ['level' => MonologLogger::WARNING]],
            [["c.log", "%c2010-10-20 10:20:30.123456 web/{$pid} [ERROR] Log Message."    , SimpleBrowserConsoleHandler::OUTPUT_STYLES[MonologLogger::ERROR][1]]    , ['level' => MonologLogger::ERROR]],
            [["c.log", "%c2010-10-20 10:20:30.123456 web/{$pid} [CRITICAL] Log Message." , SimpleBrowserConsoleHandler::OUTPUT_STYLES[MonologLogger::CRITICAL][1]] , ['level' => MonologLogger::CRITICAL]],
            [["c.log", "%c2010-10-20 10:20:30.123456 web/{$pid} [ALERT] Log Message."    , SimpleBrowserConsoleHandler::OUTPUT_STYLES[MonologLogger::ALERT][1]]    , ['level' => MonologLogger::ALERT]],
            [["c.log", "%c2010-10-20 10:20:30.123456 web/{$pid} [EMERGENCY] Log Message.", SimpleBrowserConsoleHandler::OUTPUT_STYLES[MonologLogger::EMERGENCY][1]], ['level' => MonologLogger::EMERGENCY]],

            [
                [
                    "c.groupCollapsed",
                    "c.log",
                    "c.groupEnd",
                    "====== [  CONTEXT  ] ======",
                    "%c2010-10-20 10:20:30.123456 web/{$pid} [DEBUG] Log Message.",
                    SimpleBrowserConsoleHandler::OUTPUT_STYLES[MonologLogger::DEBUG][1],
                    SimpleBrowserConsoleHandler::OUTPUT_STYLES[MonologLogger::DEBUG][2]
                ],
                ['context' => ['foo' => 'bar']]
            ],
        ];
    }

    /**
     * @dataProvider dataSends
     */
    public function test_send($expect, $record)
    {
        $handler = new SimpleBrowserConsoleHandler();
        $handler->handle($this->record($record));
        $this->assertContainsOutbuffer($expect, function () use ($handler) { $handler->send(); });
    }

    protected function record(array $diff = []) : array
    {
        return array_merge([
            'message'    => "Log Message.",
            'context'    => [
                'test'      => 'Foo Bar',
                'datetime'  => DateTime::now(),
            ],
            'level'      => MonologLogger::DEBUG,
            'level_name' => MonologLogger::getLevelName($diff['level'] ?? MonologLogger::DEBUG),
            'channel'    => 'web',
            'datetime'   => DateTime::now()->toNativeDateTime(), // Use Rebet DateTime class for create datetime.
            'extra'      => [
                'process_id' => getmypid()
            ],
        ], $diff);
    }

    public function test_close()
    {
        $handler = new SimpleBrowserConsoleHandler();
        $handler->handle($this->record());
        $handler->close();
        $this->assertSameOutbuffer('', function () use ($handler) { $handler->send(); });
    }

    public function test_reset()
    {
        $handler = new SimpleBrowserConsoleHandler();
        $handler->handle($this->record());
        $handler->reset();
        $this->assertSameOutbuffer('', function () use ($handler) { $handler->send(); });
    }

    public function test_clear()
    {
        $handler = new SimpleBrowserConsoleHandler();
        $handler->handle($this->record());
        SimpleBrowserConsoleHandler::clear();
        $this->assertSameOutbuffer('', function () use ($handler) { $handler->send(); });
    }
}
