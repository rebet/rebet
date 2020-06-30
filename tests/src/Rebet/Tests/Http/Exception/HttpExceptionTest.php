<?php
namespace Rebet\Tests\DateTime\Exception;

use Rebet\Application\App;
use Rebet\Http\Exception\HttpException;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Tests\RebetTestCase;

class HttpExceptionTest extends RebetTestCase
{
    public function test___construct()
    {
        $e = new HttpException(404, 'Page not found');
        $this->assertInstanceOf(HttpException::class, $e);
        $this->assertSame(404, $e->getStatus());
        $this->assertSame('指定のページが見つかりません', $e->getTitle());
        $this->assertSame('Page not found', $e->getDetail());

        $e = new HttpException(404, 'message.http.404.detail', 'message.http.404.title');
        $this->assertInstanceOf(HttpException::class, $e);
        $this->assertSame(404, $e->getStatus());
        $this->assertSame('指定のページが見つかりません', $e->getTitle());
        $this->assertSame('ご指定のページは見つかりませんでした。ご指定のURLが間違っているか、既にページが削除／移動された可能性があります。', $e->getDetail());

        App::setLocale('en');

        $e = new HttpException(404, 'message.http.404.detail', 'message.http.404.title');
        $this->assertInstanceOf(HttpException::class, $e);
        $this->assertSame(404, $e->getStatus());
        $this->assertSame('Custom Not Found', $e->getTitle());
        $this->assertSame('The page could not be found. The specified URL is incorrect, or the page may have already been deleted / moved.', $e->getDetail());

        $e = new HttpException(404, '詳細メッセージ直接指定', '件名直接指定');
        $this->assertInstanceOf(HttpException::class, $e);
        $this->assertSame(404, $e->getStatus());
        $this->assertSame('件名直接指定', $e->getTitle());
        $this->assertSame('詳細メッセージ直接指定', $e->getDetail());
    }

    public function test_getStatus()
    {
        $e = new HttpException(404);
        $this->assertSame(404, $e->getStatus());

        $e = new HttpException(500);
        $this->assertSame(500, $e->getStatus());
    }

    public function test_title()
    {
        $e = (new HttpException(404))->title('Title');
        $this->assertSame('Title', $e->getTitle());

        $e = (new HttpException(404))->title('message.http.404.title');
        $this->assertSame('指定のページが見つかりません', $e->getTitle());
    }

    public function test_getTitle()
    {
        $e = new HttpException(404);
        $this->assertSame('指定のページが見つかりません', $e->getTitle());

        $e = new HttpException(500);
        $this->assertSame('Internal Server Error', $e->getTitle());

        $e = new HttpException(404, null, 'Title');
        $this->assertSame('Title', $e->getTitle());

        $e = new HttpException(404, null, 'message.http.404.title');
        $this->assertSame('指定のページが見つかりません', $e->getTitle());

        App::setLocale('en');

        $e = new HttpException(404, null, 'message.http.404.title');
        $this->assertSame('Custom Not Found', $e->getTitle());

        App::setLocale('de', 'de');
        $e = new HttpException(404);
        $this->assertSame('Not Found', $e->getTitle());
    }

    public function test_detail()
    {
        $e = (new HttpException(404))->detail('Detail');
        $this->assertSame('Detail', $e->getDetail());

        $e = (new HttpException(404))->detail('message.http.404.detail');
        $this->assertSame('ご指定のページは見つかりませんでした。ご指定のURLが間違っているか、既にページが削除／移動された可能性があります。', $e->getDetail());
    }

    public function test_getDetail()
    {
        $e = new HttpException(404);
        $this->assertSame(null, $e->getDetail());

        $e = new HttpException(404, 'Detail');
        $this->assertSame('Detail', $e->getDetail());

        $e = new HttpException(404, 'message.http.404.detail');
        $this->assertSame('ご指定のページは見つかりませんでした。ご指定のURLが間違っているか、既にページが削除／移動された可能性があります。', $e->getDetail());

        App::setLocale('en');

        $e = new HttpException(404, 'message.http.404.detail');
        $this->assertSame('The page could not be found. The specified URL is incorrect, or the page may have already been deleted / moved.', $e->getDetail());
    }

    public function test_problem()
    {
        $e        = new HttpException(404, 'Detail');
        $response = $e->problem();
        $this->assertInstanceOf(ProblemResponse::class, $response);
        $this->assertSame([
            'status' => 404,
            'title'  => '指定のページが見つかりません',
            'type'   => 'about:blank',
            'detail' => 'Detail'
        ], $response->getProblem());
    }
}
