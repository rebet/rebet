<?php
namespace Rebet\Tests\Log\Driver\Monolog\Formatter;

use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use Rebet\Application\App;
use Rebet\Log\Driver\Monolog\Formatter\TextFormatter;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\DateTime\DateTime;

class TextFormatterTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        App::setLocale('ja');
        DateTime::setTestNow('2010-10-20 10:20:30.123456');
    }

    public function test___construct()
    {
        $this->assertInstanceOf(TextFormatter::class, new TextFormatter());
    }

    public static function dataFormats() : array
    {
        return [
            ["2010-10-20 10:20:30.123456 web/ [DEBUG] Log Message.\n"],
            [
                "2010-10-20 10:20:30.123456 web/123 [DEBUG] Log Message.\n"
                , ['extra' => ['process_id' => '123']]
            ],
            ["2010-10-20 10:20:30.123456 web/ [INFO] Log Message.\n", ['level' => MonologLogger::INFO]],
            [
                <<<EOS
                2010-10-20 10:20:30.123456 web/ [DEBUG] Log Message.
                ====== [  CONTEXT  ] ======
                == array:1 [
                ==     foo => FOO
                == ]
                EOS,
                ['context' => ['foo' => 'FOO']]
            ],
            [
                <<<EOS
                2010-10-20 10:20:30.123456 web [DEBUG] Log Message. <FOO>
                EOS,
                ['context' => ['foo' => 'FOO']],
                "{datetime} {channel} [{level_name}] {message} <{context.foo}>{context}{extra}{exception}\n"
            ],
            [
                <<<EOS
                2010-10-20 10:20:30.123456 web [DEBUG] Log Message. <FOO><>
                EOS,
                ['context' => ['foo' => 'FOO']],
                "{datetime} {channel} [{level_name}] {message} <{context.foo}><{context.bar}>{context}{extra}{exception}\n"
            ],

            [
                <<<EOS
                2010-10-20 10:20:30.123456 web/ [DEBUG] Log Message.
                ------ [   EXTRA   ] ------
                -- array:1 [
                --     foo => FOO
                -- ]
                EOS,
                ['extra' => ['foo' => 'FOO']]
            ],
            [
                <<<EOS
                2010-10-20 10:20:30.123456 web [DEBUG] Log Message. <FOO>
                EOS,
                ['extra' => ['foo' => 'FOO']],
                "{datetime} {channel} [{level_name}] {message} <{extra.foo}>{context}{extra}{exception}\n"
            ],
            [
                <<<EOS
                2010-10-20 10:20:30.123456 web [DEBUG] Log Message. <FOO><>
                EOS,
                ['extra' => ['foo' => 'FOO']],
                "{datetime} {channel} [{level_name}] {message} <{extra.foo}><{extra.bar}>{context}{extra}{exception}\n"
            ],
            [
                <<<EOS
                2010-10-20 10:20:30.123456 web/ [DEBUG] Log Message.
                ****** [ EXCEPTION ] ******
                ** Exception: Test Exception in 
                EOS,
                ['context' => ['exception' => new \Exception("Test Exception")]]
            ],
            [
                <<<EOS
                2010-10-20 10:20:30.123456 web/ [DEBUG] Log Message.
                ====== [  CONTEXT  ] ======
                == array:1 [
                ==     foo => FOO
                == ]
                ------ [   EXTRA   ] ------
                -- array:1 [
                --     bar => BAR
                -- ]
                ****** [ EXCEPTION ] ******
                ** Exception: Test Exception in 
                EOS,
                [
                    'context' => ['foo' => 'FOO', 'exception' => new \Exception("Test Exception")],
                    'extra'   => ['bar' => 'BAR'],
                ]
            ],
            ["2010年10月20日(水) 10:20:30.123456 web/ [DEBUG] Log Message.\n", [], null, [
                '{datetime}' => function (DateTime $val) { return $val->format('Xddd Xttt'); },
            ]],
        ];
    }

    #[DataProvider('dataFormats')]
    public function test_format($expect, array $record = [], ?string $format = null, array $stringifiers = [])
    {
        $record = array_merge([
            'message'  => 'Log Message.',
            'context'  => [],
            'level'    => MonologLogger::DEBUG,
            'channel'  => 'web',
            'datetime' => DateTime::now(), // Use Rebet DateTime class for create datetime.
            'extra'    => [],
        ], $record);
        $log_record = new LogRecord(
            datetime: $record['datetime'],
            channel: $record['channel'],
            level: MonologLogger::toMonologLevel($record['level']),
            message: $record['message'],
            context: $record['context'],
            extra: $record['extra'],
        );
        $formatter = new TextFormatter($format, $stringifiers);
        $this->assertStringContainsString($expect, $formatter->format($log_record));
    }

    public function test_formatBatch()
    {
        $formatter = new TextFormatter();
        $records   = [
            new LogRecord(
                datetime: DateTime::createDateTime('2010-10-20 10:20:30.123456'), // Use Rebet DateTime class for create datetime.
                channel: 'web',
                level: MonologLogger::toMonologLevel(MonologLogger::DEBUG),
                message: 'Log Message 1.',
                context: [],
                extra: ['process_id' => 123],
            ),
            new LogRecord(
                datetime: DateTime::createDateTime('2010-10-20 10:20:31.987654'), // Use Rebet DateTime class for create datetime.
                channel: 'web',
                level: MonologLogger::toMonologLevel(MonologLogger::INFO),
                message: 'Log Message 2.',
                context: [],
                extra: ['process_id' => 456],
            ),
        ];
        $this->assertSame(
            <<<EOS
            2010-10-20 10:20:30.123456 web/123 [DEBUG] Log Message 1.
            2010-10-20 10:20:31.987654 web/456 [INFO] Log Message 2.

            EOS,
            $formatter->formatBatch($records)
        );
    }
}
