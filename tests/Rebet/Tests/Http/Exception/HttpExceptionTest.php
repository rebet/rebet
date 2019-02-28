<?php
namespace Rebet\Tests\DateTime\Exception;

use Rebet\Foundation\App;
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
        $this->assertSame('Not Found', $e->getTitle());
        $this->assertSame('Page not found', $e->getDetail());

        $e = new HttpException(404, 'message.http.404.detail', 'message.http.404.title');
        $this->assertInstanceOf(HttpException::class, $e);
        $this->assertSame(404, $e->getStatus());
        $this->assertSame('指定のページが見つかりません', $e->getTitle());
        $this->assertSame('ご指定のページが見つかりません。TOPページから再度やり直して下さい。', $e->getDetail());

        App::setLocale('en');

        $e = new HttpException(404, 'message.http.404.detail', 'message.http.404.title');
        $this->assertInstanceOf(HttpException::class, $e);
        $this->assertSame(404, $e->getStatus());
        $this->assertSame('Custom Not Found', $e->getTitle());
        $this->assertSame('The specified page can not be found. Please try again from the TOP page.', $e->getDetail());

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

    public function test_getTitle()
    {
        $e = new HttpException(404);
        $this->assertSame('Not Found', $e->getTitle());

        $e = new HttpException(500);
        $this->assertSame('Internal Server Error', $e->getTitle());

        $e = new HttpException(404, null, 'Title');
        $this->assertSame('Title', $e->getTitle());

        $e = new HttpException(404, null, 'message.http.404.title');
        $this->assertSame('指定のページが見つかりません', $e->getTitle());

        App::setLocale('en');

        $e = new HttpException(404, null, 'message.http.404.title');
        $this->assertSame('Custom Not Found', $e->getTitle());
    }

    public function test_getDetail()
    {
        $e = new HttpException(404);
        $this->assertSame(null, $e->getDetail());

        $e = new HttpException(404, 'Detail');
        $this->assertSame('Detail', $e->getDetail());

        $e = new HttpException(404, 'message.http.404.detail');
        $this->assertSame('ご指定のページが見つかりません。TOPページから再度やり直して下さい。', $e->getDetail());

        App::setLocale('en');

        $e = new HttpException(404, 'message.http.404.detail');
        $this->assertSame('The specified page can not be found. Please try again from the TOP page.', $e->getDetail());
    }

    public function test_problem()
    {
        $e        = new HttpException(404, 'Detail');
        $responce = $e->problem();
        $this->assertInstanceOf(ProblemResponse::class, $responce);
        $this->assertSame([
            'status' => 404,
            'title'  => 'Not Found',
            'type'   => 'about:blank',
            'detail' => 'Detail'
        ], $responce->getProblem());
    }
}
