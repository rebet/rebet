<?php

declare(strict_types=1);

namespace Rebet\Tests\Auth\Exception;

use Rebet\Auth\Exception\AuthenticateException;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Tests\RebetTestCase;

class AuthenticateExceptionTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $e = new AuthenticateException('test');
        $this->assertInstanceOf(AuthenticateException::class, $e);
    }

    public function test_problem(): void
    {
        $e       = new AuthenticateException('test');
        $problem = $e->problem();
        $this->assertInstanceOf(ProblemResponse::class, $problem);
        $this->assertSame([
            'status' => 403,
            'title'  => 'Forbidden',
            'type'   => 'about:blank',
            'detail' => 'test',
        ], $problem->getProblem());
    }
}
