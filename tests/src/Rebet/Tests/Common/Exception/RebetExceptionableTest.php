<?php
namespace Rebet\Tests\Common\Exception;

use Rebet\Common\Exception\LogicException;
use Rebet\Common\Exception\RuntimeException;
use Rebet\Tests\RebetTestCase;

class RebetExceptionableTest extends RebetTestCase
{
    public function test_caused()
    {
        $cause = new RuntimeException('cause');
        $e     = (new LogicException('test'))->caused($cause);
        $this->assertSame($cause, $e->getCaused());
    }

    public function test_getCaused()
    {
        $cause = new RuntimeException('cause');
        $e     = new LogicException('test', $cause);
        $this->assertSame($cause, $e->getCaused());
    }

    public function test_code()
    {
        $e = (new LogicException('test'))->code(500);
        $this->assertSame(500, $e->getCode());

        $e = (new LogicException('test'))->code('ERR001');
        $this->assertSame('ERR001', $e->getCode());
    }

    public function test_appendix()
    {
        $e = (new LogicException('test'))->appendix([1, 2, 3]);
        $this->assertSame([1, 2, 3], $e->getAppendix());
    }

    public function test___toString()
    {
        $e = (new LogicException('test'))->appendix([1, 2, 3]);
        $this->assertContains("Appendix:", "{$e}");
    }
}
