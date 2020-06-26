<?php
namespace Rebet\Tests\Database\Exception;

use Rebet\Database\Exception\DatabaseException;
use Rebet\Tests\RebetTestCase;

class DatabaseExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new DatabaseException('test');
        $this->assertInstanceOf(DatabaseException::class, $e);
    }
}
