<?php
namespace Rebet\Tests\DateTime\Exception;

use Rebet\Http\Exception\TokenMismatchException;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Tests\RebetTestCase;

class TokenMismatchExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new TokenMismatchException('Message');
        $this->assertInstanceOf(TokenMismatchException::class, $e);
    }

    public function test_problem()
    {
        $e        = new TokenMismatchException('Message');
        $responce = $e->problem();
        $this->assertInstanceOf(ProblemResponse::class, $responce);
        $this->assertSame([
            'status' => 400,
            'title'  => 'Bad Request',
            'type'   => 'about:blank',
            'detail' => 'Message',
        ], $responce->getProblem());
    }
}
