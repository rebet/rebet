<?php
namespace Rebet\Tests\Log\Driver\Monolog\Formatter;

use Psr\Log\LogLevel;
use Rebet\DateTime\DateTime;
use Rebet\Log\Driver\Monolog\Formatter\TextFormatter;
use Rebet\Tests\RebetTestCase;

class TextFormatterTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2010-10-20 10:20:30.123456');
    }

    public function test___construct()
    {
        $this->assertInstanceOf(TextFormatter::class, new TextFormatter());
    }

    public function dataFormats() : array
    {
        return [
            ["2010-10-20 10:20:30.123456 web [DEBUG] Log Message.\n"],
            ["2010-10-20 10:20:30.123456 web [INFO] Log Message.\n", ['level_name' => 'INFO']],
            [
                <<<EOS
2010-10-20 10:20:30.123456 web [DEBUG] Log Message.
====== [  CONTEXT  ] ======
== array:1 [
==     foo => FOO
== ]
EOS
                , ['context' => ['foo' => 'FOO']]
            ],
            [
                <<<EOS
2010-10-20 10:20:30.123456 web [DEBUG] Log Message. <FOO>
EOS
                , ['context' => ['foo' => 'FOO']]
                , "{datetime} {channel} [{level_name}] {message} <{context.foo}>{context}{extra}{exception}\n"
            ],
            [
                <<<EOS
2010-10-20 10:20:30.123456 web [DEBUG] Log Message. <FOO><>
EOS
                , ['context' => ['foo' => 'FOO']]
                , "{datetime} {channel} [{level_name}] {message} <{context.foo}><{context.bar}>{context}{extra}{exception}\n"
            ],

            [
                <<<EOS
2010-10-20 10:20:30.123456 web [DEBUG] Log Message.
------ [   EXTRA   ] ------
-- array:1 [
--     foo => FOO
-- ]
EOS
                , ['extra' => ['foo' => 'FOO']]
            ],
            [
                <<<EOS
2010-10-20 10:20:30.123456 web [DEBUG] Log Message. <FOO>
EOS
                , ['extra' => ['foo' => 'FOO']]
                , "{datetime} {channel} [{level_name}] {message} <{extra.foo}>{context}{extra}{exception}\n"
            ],
            [
                <<<EOS
2010-10-20 10:20:30.123456 web [DEBUG] Log Message. <FOO><>
EOS
                , ['extra' => ['foo' => 'FOO']]
                , "{datetime} {channel} [{level_name}] {message} <{extra.foo}><{extra.bar}>{context}{extra}{exception}\n"
            ],
            [
                <<<EOS
2010-10-20 10:20:30.123456 web [DEBUG] Log Message.
****** [ EXCEPTION ] ******
** Exception: Test Exception in 
EOS
                , ['exception' => new \Exception("Test Exception")]
            ],
            [
                <<<EOS
2010-10-20 10:20:30.123456 web [DEBUG] Log Message.
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
EOS
                , [
                    'context'   => ['foo' => 'FOO'],
                    'extra'     => ['bar' => 'BAR'],
                    'exception' => new \Exception("Test Exception")
                ]
            ],
            [
                <<<EOS
2010-10-20 10:20:30.123456 web [DEBUG] array:1 [
    foo => FOO
]
EOS
                , [
                    'message' => ['foo' => 'FOO'],
                ]
            ],
            ["2010年10月20日(水) 10:20:30.123456 web [DEBUG] Log Message.\n", [], null, [
                '{datetime}'  => function (DateTime $val) { return $val->format('Xddd Xttt'); },
            ]],
        ];
    }

    /**
     * @dataProvider dataFormats
     */
    public function test_format($expect, array $record = [], ?string $format = null, array $stringifiers = [])
    {
        $record = array_merge([
            'message'    => 'Log Message.',
            'context'    => [],
            'level'      => LogLevel::DEBUG,
            'level_name' => 'DEBUG',
            'channel'    => 'web',
            'datetime'   => DateTime::now()->toNativeDateTime(), // Use Rebet DateTime class for create datetime.
            'extra'      => [],
        ], $record);
        $formatter = new TextFormatter($format, $stringifiers);
        $this->assertContains($expect, $formatter->format($record));
    }

    public function test_formatBatch()
    {
        $formatter = new TextFormatter();
        $records   = [
            [
                'message'    => 'Log Message 1.',
                'context'    => [],
                'level'      => LogLevel::DEBUG,
                'level_name' => 'DEBUG',
                'channel'    => 'web',
                'datetime'   => DateTime::createDateTime('2010-10-20 10:20:30.123456')->toNativeDateTime(), // Use Rebet DateTime class for create datetime.
                'extra'      => [],
            ],
            [
                'message'    => 'Log Message 2.',
                'context'    => [],
                'level'      => LogLevel::INFO,
                'level_name' => 'INFO',
                'channel'    => 'web',
                'datetime'   => DateTime::createDateTime('2010-10-20 10:20:31.987654')->toNativeDateTime(), // Use Rebet DateTime class for create datetime.
                'extra'      => [],
            ],
        ];
        $this->assertSame(
            <<<EOS
2010-10-20 10:20:30.123456 web [DEBUG] Log Message 1.
2010-10-20 10:20:31.987654 web [INFO] Log Message 2.

EOS
            ,
            $formatter->formatBatch($records)
        );
    }
}
