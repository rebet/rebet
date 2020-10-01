<?php
namespace Rebet\Tests\Http\Response;

use Rebet\Application\App;
use Rebet\Tools\Exception\LogicException;
use Rebet\Http\Response;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Tests\RebetTestCase;

class ProblemResponseTest extends RebetTestCase
{
    public function test___construct()
    {
        $response = new ProblemResponse(404);
        $this->assertInstanceOf(ProblemResponse::class, $response);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/problem+json', $response->getHeader('Content-Type'));
        $this->assertSame([
            'status' => 404,
            'title'  => '指定のページが見つかりません',
            'type'   => ProblemResponse::TYPE_HTTP_STATUS,
        ], $response->getProblem());
        $this->assertSame(null, $response->getProblem('detail'));

        $response = new ProblemResponse(400, 'New Title', ProblemResponse::TYPE_FALLBACK_ERRORS);
        $this->assertSame([
            'status' => 400,
            'title'  => 'New Title',
            'type'   => ProblemResponse::TYPE_FALLBACK_ERRORS,
        ], $response->getProblem());

        App::setLocale('en');
        $response = new ProblemResponse(404);
        $this->assertSame('Custom Not Found', $response->getProblem('title'));

        App::setLocale('de', 'en');
        $response = new ProblemResponse(404);
        $this->assertSame('Custom Not Found', $response->getProblem('title'));

        App::setLocale('de', 'de');
        $response = new ProblemResponse(404);
        $this->assertSame('Not Found', $response->getProblem('title'));
    }

    public function test_detail()
    {
        $response = (new ProblemResponse(404))->detail('Detail');
        $this->assertSame('Detail', $response->getProblem('detail'));

        $response = (new ProblemResponse(404))->detail('message.http.404.detail');
        $this->assertSame('ご指定のページは見つかりませんでした。ご指定のURLが間違っているか、既にページが削除／移動された可能性があります。', $response->getProblem('detail'));
    }

    public function test_instance()
    {
        $response = (new ProblemResponse(404))->instance('Instance');
        $this->assertSame('Instance', $response->getProblem('instance'));
    }

    public function test_additional_invalidType()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The type of 'about:blank' can not contains additional.");

        $response = (new ProblemResponse(404))->additional('foo', 'bar');
    }

    public function test_additional_reservedWord_status()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The key of 'status' is reserved. so you can't set 'status' via additional.");

        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional('status', 200);
    }

    public function test_additional_array_reservedWord_status()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The key of 'status' is reserved. so you can't set 'status' via additional.");

        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional(['status' => 200]);
    }

    public function test_additional_reservedWord_title()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The key of 'title' is reserved. so you can't set 'title' via additional.");

        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional('title', 'new');
    }

    public function test_additional_array_reservedWord_title()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The key of 'title' is reserved. so you can't set 'title' via additional.");

        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional(['title' => 'new']);
    }

    public function test_additional_reservedWord_detail()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The key of 'detail' is reserved. so you can't set 'detail' via additional.");

        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional('detail', 'new');
    }

    public function test_additional_array_reservedWord_detail()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The key of 'detail' is reserved. so you can't set 'detail' via additional.");

        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional(['detail' => 'new']);
    }

    public function test_additional_reservedWord_instance()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The key of 'instance' is reserved. so you can't set 'instance' via additional.");

        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional('instance', 'new');
    }

    public function test_additional_array_reservedWord_instance()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The key of 'instance' is reserved. so you can't set 'instance' via additional.");

        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional(['instance' => 'new']);
    }

    public function test_additional()
    {
        $response = (new ProblemResponse(404, 'Fallback', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional('foo', 'bar');
        $this->assertSame('bar', $response->getProblem('foo'));

        $response = (new ProblemResponse(404, 'Fallback', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional(['foo' => 'bar']);
        $this->assertSame('bar', $response->getProblem('foo'));
    }

    public function test_getProblem()
    {
        $response = (new ProblemResponse(400, 'New Title', ProblemResponse::TYPE_FALLBACK_ERRORS))
                    ->detail('Detail')
                    ->instance('Instance')
                    ->additional([
                        'input' => [
                            'name' => null,
                        ],
                        'errors' => [
                            'name' => [
                                'Name is required.'
                            ]
                        ]
                    ]);

        $this->assertSame([
            'status'   => 400,
            'title'    => 'New Title',
            'type'     => ProblemResponse::TYPE_FALLBACK_ERRORS,
            'detail'   => 'Detail',
            'instance' => 'Instance',
            'input'    => ['name' => null],
            'errors'   => [
                'name' => [
                    'Name is required.'
                ]
            ]
        ], $response->getProblem());

        $this->assertSame(400, $response->getProblem('status'));
        $this->assertSame(['name' => null], $response->getProblem('input'));
        $this->assertSame(null, $response->getProblem('input.name'));
        $this->assertSame(['Name is required.'], $response->getProblem('errors.name'));
        $this->assertSame(null, $response->getProblem('nothing'));
    }
}
