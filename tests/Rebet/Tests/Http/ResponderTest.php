<?php
namespace Rebet\Tests\Http;

use Rebet\Config\Config;
use Rebet\Filesystem\Storage;
use Rebet\Foundation\App;
use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Http\Response\JsonResponse;
use Rebet\Http\Response\ProblemResponse;
use Rebet\Http\Response\RedirectResponse;
use Rebet\Http\Response\StreamedResponse;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\View;

class ResponderTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->vfs([
            'cache' => [],
        ]);
    }

    public function tearDown()
    {
        parent::tearDown();
        Storage::clean();
    }

    public function test_toResponse()
    {
        $response = Responder::toResponse('text');
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('text', $response->getContent());

        $response_org = new BasicResponse('response');
        $response     = Responder::toResponse($response_org);
        $this->assertSame($response_org, $response);

        $response = Responder::toResponse(['key' => 'value']);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame('{"key":"value"}', $response->getContent());

        $response = Responder::toResponse(Gender::MALE());
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame('1', $response->getContent());

        $response = Responder::toResponse(function () { return "test"; });
        $this->assertInstanceOf(StreamedResponse::class, $response);

        Config::application([
            View::class => [
                'engine' => Blade::class,
            ],
            Blade::class => [
                'view_path'  => [App::path('/resources/views/blade')],
                'cache_path' => 'vfs://root/cache',
            ],
        ]);

        $response = Responder::toResponse(View::of('welcome')->with('name', 'Rebet'));
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('Hello, Rebet.', $response->getContent());
    }

    public function dataRedirects() : array
    {
        return [
            ['/redirect/to', '/redirect/to', [], 302, ''],
            ['/redirect/to', '@/redirect/to', [], 302, ''],
            ['/redirect/to?key=value', '/redirect/to', ['key' => 'value'], 302, ''],
            ['/redirect/to?key=value', '@/redirect/to', ['key' => 'value'], 302, ''],
            ['/redirect/to?foo=bar&key=value', '/redirect/to?foo=bar', ['key' => 'value'], 302, ''],
            ['/redirect/to', '/redirect/to', [], 307, ''],
            ['/redirect/to', '@/redirect/to', [], 307, ''],
            ['/prefix/redirect/to', '/redirect/to', [], 302, '/prefix'],
            ['/redirect/to', '@/redirect/to', [], 302, '/prefix'],
            ['https://rebet.local/redirect/to', 'https://rebet.local/redirect/to', [], 302, '/prefix'],
            ['/prefix/redirect/to?foo=bar&key=value', '/redirect/to?foo=bar', ['key' => 'value'], 307, '/prefix'],
        ];
    }

    /**
     * @dataProvider dataRedirects
     */
    public function test_redirect($expect, $path, $query, $status, $prefix)
    {
        $request  = $this->createRequestMock("{$prefix}/", null, 'web', 'GET', $prefix);
        $response = Responder::redirect($path, $query, $status, [], $request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($expect, $response->getTargetUrl());
        $this->assertSame($status, $response->getStatusCode());
    }

    public function test_problem()
    {
        $this->assertInstanceOf(ProblemResponse::class, Responder::problem(500));
    }

    /**
     * @expectedException Rebet\Filesystem\Exception\FileNotFoundException
     * @expectedExceptionMessage File not found at path: nothing.txt
     */
    public function test_file_nothing()
    {
        $response = Responder::file('nothing.txt');
        $response->sendContent();
    }

    public function test_file()
    {
        Storage::private()->put('foo.txt', 'foo');
        $response = Responder::file('foo.txt');
        $this->assertSame('text/plain', $response->getHeader('Content-Type'));
        $this->assertSame(3, $response->getHeader('Content-Length'));
        $this->assertSame("inline; filename=".md5('foo.txt').".txt; filename*=utf-8''foo.txt", $response->getHeader('Content-Disposition'));
        $this->assertSameOutbuffer('foo', function () use ($response) {
            $response->sendContent();
        });
    }

    public function test_download()
    {
        Storage::private()->put('foo.csv', '1,2,3');
        $response = Responder::download('foo.csv');
        $this->assertSame('text/csv', $response->getHeader('Content-Type'));
        $this->assertSame(5, $response->getHeader('Content-Length'));
        $this->assertSame("attachment; filename=".md5('foo.csv').".csv; filename*=utf-8''foo.csv", $response->getHeader('Content-Disposition'));
        $this->assertSameOutbuffer('1,2,3', function () use ($response) {
            $response->sendContent();
        });
    }
}
