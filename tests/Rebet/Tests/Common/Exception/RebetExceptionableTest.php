<?php
namespace Rebet\Tests\Common\Exception;

use Rebet\Common\Exception\LogicException;
use Rebet\Common\Exception\RebetException;
use Rebet\Common\Exception\RuntimeException;
use Rebet\Tests\RebetTestCase;

class RebetExceptionableTest extends RebetTestCase
{
    public function test_by()
    {
        $e = LogicException::by('test');
        $this->assertInstanceOf(LogicException::class, $e);
        $this->assertInstanceOf(RebetException::class, $e);
        $this->assertInstanceOf(\LogicException::class, $e);
    }
    
    public function test_caused()
    {
        $cause = RuntimeException::by('cause');
        $e     = LogicException::by('test')->caused($cause);
        $this->assertSame($cause, $e->getCaused());
    }

    public function test_getCaused()
    {
        $cause = RuntimeException::by('cause');
        $e     = new LogicException('test', $cause);
        $this->assertSame($cause, $e->getCaused());
    }
    
    public function test_code()
    {
        $e = LogicException::by('test')->code(500);
        $this->assertSame(500, $e->getCode());
        
        $e = LogicException::by('test')->code('ERR001');
        $this->assertSame('ERR001', $e->getCode());
    }
    
    public function test_appendix()
    {
        $e = LogicException::by('test')->appendix([1, 2, 3]);
        $this->assertSame([1, 2, 3], $e->getAppendix());
    }
    
    public function test___toString()
    {
        $e = LogicException::by('test')->appendix([1, 2, 3]);
        $this->assertContains("Appendix:", "{$e}");
    }
}
