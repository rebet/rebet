<?php
namespace Rebet\Tests\Tools\Utility;

use Exception;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Utility\Dsn;

class DsnTest extends RebetTestCase
{
    /**
     * @dataProvider dataParses
     */
    public function test_parse($dsn, $expect)
    {
        try {
            $this->assertSame($expect, Dsn::parse($dsn));
        } catch (Exception $e) {
            if ($expect instanceof Exception) {
                $this->assertSame($expect->getMessage(), $e->getMessage());
                $this->assertInstanceOf(get_class($expect), $e);
            } else {
                throw $e;
            }
        }
    }

    public function dataParses() : array
    {
        return [
            [null, []],
            ['', []],

            ['localhost', ['host' => 'localhost']],
            ['pass@localhost', ['pass' => 'pass', 'host' => 'localhost']],
            ['user:pass@localhost', ['user' => 'user', 'pass' => 'pass', 'host' => 'localhost']],
            ['scheme://localhost', ['scheme' => 'scheme', 'host' => 'localhost']],
            ['scheme://pass@localhost', ['scheme' => 'scheme', 'pass' => 'pass', 'host' => 'localhost']],
            ['scheme://user:pass@localhost', ['scheme' => 'scheme', 'user' => 'user', 'pass' => 'pass', 'host' => 'localhost']],

            ['localhost:123', ['host' => 'localhost', 'port' => '123']],
            ['localhost/name', ['host' => 'localhost', 'name' => 'name']],
            ['localhost:123/name', ['host' => 'localhost', 'port' => '123', 'name' => 'name']],
            ['localhost?key=value', ['host' => 'localhost', 'query' => ['key' => 'value']]],
            ['localhost?key=value&foo=bar', ['host' => 'localhost', 'query' => ['key' => 'value', 'foo' => 'bar']]],
            ['localhost:123?key=value', ['host' => 'localhost', 'port' => '123', 'query' => ['key' => 'value']]],
            ['localhost/name?key=value', ['host' => 'localhost', 'name' => 'name', 'query' => ['key' => 'value']]],
            ['localhost:123/name?key=value', ['host' => 'localhost', 'port' => '123', 'name' => 'name', 'query' => ['key' => 'value']]],

            ['scheme://user:pass@localhost:123/name?key=value&foo=bar', ['scheme' => 'scheme', 'user' => 'user', 'pass' => 'pass', 'host' => 'localhost', 'port' => '123', 'name' => 'name', 'query' => ['key' => 'value', 'foo' => 'bar']]],

            ['scheme://', new LogicException("DSN parse failed, the DSN must have host part but given DSN 'scheme://' not include it.")],
            ['scheme://:123', new LogicException("DSN parse failed, the DSN must have host part but given DSN 'scheme://:123' not include it.")],
            [':123', new LogicException("DSN parse failed, the DSN must have host part but given DSN ':123' not include it.")],
            ['/name', new LogicException("DSN parse failed, the DSN must have host part but given DSN '/name' not include it.")],
            ['?key=value', new LogicException("DSN parse failed, the DSN must have host part but given DSN '?key=value' not include it.")],
            ['pass@', new LogicException("DSN parse failed, the DSN must have host part but given DSN 'pass@' not include it.")],
            ['user:pass@', new LogicException("DSN parse failed, the DSN must have host part but given DSN 'user:pass@' not include it.")],
        ];
    }
}
