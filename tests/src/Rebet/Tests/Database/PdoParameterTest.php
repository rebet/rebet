<?php

declare(strict_types=1);

namespace Rebet\Tests\Database;

use Rebet\Database\PdoParameter;
use Rebet\Tests\RebetTestCase;

class PdoParameterTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $this->assertInstanceOf(PdoParameter::class, new PdoParameter('foo'));
    }

    public function test___toString(): void
    {
        $p = new PdoParameter('foo');
        $this->assertSame('[STR] foo', $p->__toString());

        $p = new PdoParameter(123, \PDO::PARAM_INT);
        $this->assertSame('[INT] 123', $p->__toString());

        $this->assertSame('[BOOL] 1', (new PdoParameter(true, \PDO::PARAM_BOOL))->__toString());
        $this->assertSame('[LOB] data', (new PdoParameter('data', \PDO::PARAM_LOB))->__toString());
        $this->assertSame('[NULL] ', (new PdoParameter(null, \PDO::PARAM_NULL))->__toString());
        $this->assertSame('[TYPE(99)] foo', (new PdoParameter('foo', 99))->__toString());
    }

    public function test_str(): void
    {
        $p = PdoParameter::str('foo');
        $this->assertSame(\PDO::PARAM_STR, $p->type);
    }

    public function test_int(): void
    {
        $p = PdoParameter::int('foo');
        $this->assertSame(\PDO::PARAM_INT, $p->type);
    }

    public function test_bool(): void
    {
        $p = PdoParameter::bool(true);
        $this->assertSame(\PDO::PARAM_BOOL, $p->type);
    }

    public function test_lob(): void
    {
        $p = PdoParameter::lob('foo');
        $this->assertSame(\PDO::PARAM_LOB, $p->type);
    }

    public function test_null(): void
    {
        $p = PdoParameter::null();
        $this->assertSame(\PDO::PARAM_NULL, $p->type);
        $this->assertSame(null, $p->value);
    }
}
