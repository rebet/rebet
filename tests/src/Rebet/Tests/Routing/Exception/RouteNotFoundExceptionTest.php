<?php
namespace Rebet\Tests\Routing\Exception;

use Rebet\Http\Response\ProblemResponse;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Tests\RebetTestCase;

class RouteNotFoundExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new RouteNotFoundException('test');
        $this->assertInstanceOf(RouteNotFoundException::class, $e);
    }

    public function test_problem()
    {
        $e       = new RouteNotFoundException('test');
        $problem = $e->problem();
        $this->assertInstanceOf(ProblemResponse::class, $problem);
        $this->assertSame([
            'status' => 404,
            'title'  => '指定のページが見つかりません',
            'type'   => 'about:blank',
            'detail' => 'ご指定のページは見つかりませんでした。ご指定のURLが間違っているか、既にページが削除／移動された可能性があります。',
        ], $problem->getProblem());
    }
}
