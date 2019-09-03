<?php
namespace Rebet\Tests\Database;

use Rebet\Database\PdoParameter;
use Rebet\Tests\RebetTestCase;

class PdoParameterTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(PdoParameter::class, new PdoParameter('foo'));
    }

    public function test___toString()
    {
        $p = new PdoParameter('foo');
        $this->assertSame('[STR] foo', $p->__toString());

        $p = new PdoParameter(123, \PDO::PARAM_INT);
        $this->assertSame('[INT] 123', $p->__toString());
    }

    public function test_str()
    {
        $p = PdoParameter::str('foo');
        $this->assertSame(\PDO::PARAM_STR, $p->type);
    }

    public function test_int()
    {
        $p = PdoParameter::int('foo');
        $this->assertSame(\PDO::PARAM_INT, $p->type);
    }

    public function test_bool()
    {
        $p = PdoParameter::bool(true);
        $this->assertSame(\PDO::PARAM_BOOL, $p->type);
    }

    public function test_lob()
    {
        $p = PdoParameter::lob('foo');
        $this->assertSame(\PDO::PARAM_LOB, $p->type);
    }

    public function test_null()
    {
        $p = PdoParameter::null();
        $this->assertSame(\PDO::PARAM_NULL, $p->type);
        $this->assertSame(null, $p->value);
    }
}
