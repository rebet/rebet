<?php
namespace Rebet\Tests\Http;

use Rebet\Application\App;
use Rebet\Http\Exception\HttpException;
use Rebet\Http\HttpStatus;
use Rebet\Tests\RebetTestCase;

class HttpStatusTest extends RebetTestCase
{
    public function test_reasonPhraseOf()
    {
        $this->assertSame(null, HttpStatus::reasonPhraseOf(999));
        $this->assertSame('Continue', HttpStatus::reasonPhraseOf(100));
        $this->assertSame('OK', HttpStatus::reasonPhraseOf(200));
        $this->assertSame('Not Found', HttpStatus::reasonPhraseOf(404));
    }

    public function test_exists()
    {
        $this->assertSame(false, HttpStatus::exists(999));
        $this->assertSame(true, HttpStatus::exists(100));
        $this->assertSame(true, HttpStatus::exists(200));
        $this->assertSame(true, HttpStatus::exists(404));
    }

    public function test_classOf()
    {
        $this->assertSame(null, HttpStatus::classOf(999));
        $this->assertSame(null, HttpStatus::classOf(199));
        $this->assertSame(HttpStatus::INFORMATIONAL, HttpStatus::classOf(100));
        $this->assertSame(HttpStatus::SUCCESSFUL, HttpStatus::classOf(200));
        $this->assertSame(HttpStatus::CLIENT_ERROR, HttpStatus::classOf(404));
    }

    public function test_isInformational()
    {
        $this->assertSame(false, HttpStatus::isInformational(999));
        $this->assertSame(false, HttpStatus::isInformational(199));

        $this->assertSame(true, HttpStatus::isInformational(100));
        $this->assertSame(false, HttpStatus::isInformational(200));
        $this->assertSame(false, HttpStatus::isInformational(300));
        $this->assertSame(false, HttpStatus::isInformational(400));
        $this->assertSame(false, HttpStatus::isInformational(500));
    }

    public function test_isSuccessful()
    {
        $this->assertSame(false, HttpStatus::isSuccessful(999));
        $this->assertSame(false, HttpStatus::isSuccessful(299));

        $this->assertSame(false, HttpStatus::isSuccessful(100));
        $this->assertSame(true, HttpStatus::isSuccessful(200));
        $this->assertSame(false, HttpStatus::isSuccessful(300));
        $this->assertSame(false, HttpStatus::isSuccessful(400));
        $this->assertSame(false, HttpStatus::isSuccessful(500));
    }

    public function test_isRedirection()
    {
        $this->assertSame(false, HttpStatus::isRedirection(999));
        $this->assertSame(false, HttpStatus::isRedirection(399));

        $this->assertSame(false, HttpStatus::isRedirection(100));
        $this->assertSame(false, HttpStatus::isRedirection(200));
        $this->assertSame(true, HttpStatus::isRedirection(300));
        $this->assertSame(false, HttpStatus::isRedirection(400));
        $this->assertSame(false, HttpStatus::isRedirection(500));
    }

    public function test_isClientError()
    {
        $this->assertSame(false, HttpStatus::isClientError(999));
        $this->assertSame(false, HttpStatus::isClientError(499));

        $this->assertSame(false, HttpStatus::isClientError(100));
        $this->assertSame(false, HttpStatus::isClientError(200));
        $this->assertSame(false, HttpStatus::isClientError(300));
        $this->assertSame(true, HttpStatus::isClientError(400));
        $this->assertSame(false, HttpStatus::isClientError(500));
    }

    public function test_isServerError()
    {
        $this->assertSame(false, HttpStatus::isServerError(999));
        $this->assertSame(false, HttpStatus::isServerError(599));

        $this->assertSame(false, HttpStatus::isServerError(100));
        $this->assertSame(false, HttpStatus::isServerError(200));
        $this->assertSame(false, HttpStatus::isServerError(300));
        $this->assertSame(false, HttpStatus::isServerError(400));
        $this->assertSame(true, HttpStatus::isServerError(500));
    }

    public function test_abort_ja()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("404 指定のページが見つかりません");

        HttpStatus::abort(404);
    }

    public function test_abort_en()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("404 Custom Not Found");

        App::setLocale('en');
        HttpStatus::abort(404);
    }

    public function test_abort_none()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("404 Not Found");

        App::setLocale('none', 'none');
        HttpStatus::abort(404);
    }

    public function test_abort_detail()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("404 Not Found: This is detail.");

        App::setLocale('none', 'none');
        HttpStatus::abort(404, 'This is detail.');
    }

    public function test_abort_detailAndTitle()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("New Title: This is detail.");

        App::setLocale('none', 'none');
        HttpStatus::abort(404, 'This is detail.', 'New Title');
    }
}
