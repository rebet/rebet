<?php

declare(strict_types=1);

namespace Rebet\Tests\Tools\Exception;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Exception\RuntimeException;

class RebetExceptionableTest extends RebetTestCase
{
    public function test_caused(): void
    {
        $cause = new RuntimeException('cause');
        $e     = (new LogicException('test'))->caused($cause);
        $this->assertSame($cause, $e->getCaused());
    }

    public function test_getCaused(): void
    {
        $cause = new RuntimeException('cause');
        $e     = new LogicException('test', $cause);
        $this->assertSame($cause, $e->getCaused());
    }

    public function test_code(): void
    {
        $e = (new LogicException('test'))->code(500);
        $this->assertSame(500, $e->getCode());

        $e = (new LogicException('test'))->code('ERR001');
        $this->assertSame('ERR001', $e->getCode());
    }

    public function test_appendix(): void
    {
        $e = (new LogicException('test'))->appendix([1, 2, 3]);
        $this->assertSame([1, 2, 3], $e->getAppendix());
    }

    public function test___toString(): void
    {
        $e = (new LogicException('test'))->appendix([1, 2, 3]);
        $this->assertStringContainsString("Appendix:", "{$e}");
    }
}
