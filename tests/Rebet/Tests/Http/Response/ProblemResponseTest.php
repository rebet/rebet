<?php
namespace Rebet\Tests\Http\Response;

use Rebet\Foundation\App;
use Rebet\Http\Response;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Tests\RebetTestCase;
use Rebet\Translation\Translator;

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

        App::setLocale('de');
        $response = new ProblemResponse(404);
        $this->assertSame('Custom Not Found', $response->getProblem('title'));

        Translator::setFallbackLocale('de');
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

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage The type of 'about:blank' can not contains additional.
     */
    public function test_additional_invalidType()
    {
        $response = (new ProblemResponse(404))->additional('foo', 'bar');
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage The key of 'status' is reserved. so you can't set 'status' via additional.
     */
    public function test_additional_reservedWord_status()
    {
        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional('status', 200);
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage The key of 'status' is reserved. so you can't set 'status' via additional.
     */
    public function test_additional_array_reservedWord_status()
    {
        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional(['status' => 200]);
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage The key of 'title' is reserved. so you can't set 'title' via additional.
     */
    public function test_additional_reservedWord_title()
    {
        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional('title', 'new');
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage The key of 'title' is reserved. so you can't set 'title' via additional.
     */
    public function test_additional_array_reservedWord_title()
    {
        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional(['title' => 'new']);
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage The key of 'detail' is reserved. so you can't set 'detail' via additional.
     */
    public function test_additional_reservedWord_detail()
    {
        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional('detail', 'new');
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage The key of 'detail' is reserved. so you can't set 'detail' via additional.
     */
    public function test_additional_array_reservedWord_detail()
    {
        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional(['detail' => 'new']);
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage The key of 'instance' is reserved. so you can't set 'instance' via additional.
     */
    public function test_additional_reservedWord_instance()
    {
        $response = (new ProblemResponse(404, 'New title', ProblemResponse::TYPE_FALLBACK_ERRORS))->additional('instance', 'new');
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage The key of 'instance' is reserved. so you can't set 'instance' via additional.
     */
    public function test_additional_array_reservedWord_instance()
    {
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
