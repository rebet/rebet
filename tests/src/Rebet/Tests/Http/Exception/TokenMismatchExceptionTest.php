<?php

declare(strict_types=1);

namespace Rebet\Tests\Tools\DateTime\Exception;

use Rebet\Http\Exception\TokenMismatchException;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Tests\RebetTestCase;

class TokenMismatchExceptionTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $e = new TokenMismatchException('Message');
        $this->assertInstanceOf(TokenMismatchException::class, $e);
    }

    public function test_problem(): void
    {
        $e        = new TokenMismatchException('Message');
        $response = $e->problem();
        $this->assertInstanceOf(ProblemResponse::class, $response);
        $this->assertSame([
            'status' => 400,
            'title'  => 'Bad Request',
            'type'   => 'about:blank',
            'detail' => 'Message',
        ], $response->getProblem());
    }
}
