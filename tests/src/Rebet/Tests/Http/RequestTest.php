<?php
namespace Rebet\Tests\Http;

use BadMethodCallException;
use Rebet\Application\App;
use Rebet\Http\Bag\FileBag;
use Rebet\Http\Exception\FallbackRedirectException;
use Rebet\Http\Request;
use Rebet\Http\Response\RedirectResponse;
use Rebet\Http\Session\Session;
use Rebet\Http\UploadedFile;
use Rebet\Http\UserAgent;
use Rebet\Routing\Exception\RouteNotFoundException;
use Rebet\Routing\Router;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Validation\Valid;
use Rebet\View\Engine\Blade\Blade;
use Rebet\View\View;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;

class RequestTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        $this->vfs([
            'cache' => [],
        ]);
        Blade::clear();
        Config::application([
            View::class => [
                'engine' => Blade::class,
            ],
            Blade::class => [
                'view_path'  => [App::structure()->views('/blade')],
                'cache_path' => 'vfs://root/cache',
            ],
        ]);
    }

    public function test___construct()
    {
        $this->assertInstanceOf(Request::class, new Request());
    }

    public function test_clear()
    {
        $request = new Request();
        $this->assertNotNull(Request::current());
        Request::clear();
        $this->assertNull(Request::current());
    }

    public function test_current()
    {
        Request::clear();
        $this->assertNull(Request::current());
        $request = new Request();
        $this->assertSame($request, Request::current());
    }

    public function test_validate()
    {
        $rule = [
            'name' => [
                'rule' => [
                    ['CU', Valid::REQUIRED],
                    ['CU', Valid::MAX_LENGTH, 20],
                    ['CU', Valid::DEPENDENCE_CHAR],
                ]
            ]
        ];

        $request = Request::create('/');
        $request->request->set('name', 'John Smith');
        $valid_data = $request->validate('U', $rule, '/fallback/url');
        $this->assertSame('John Smith', $valid_data->name);
    }

    public function test_validate_error()
    {
        $this->expectException(FallbackRedirectException::class);
        $this->expectExceptionMessage("Validate Failed.");

        App::setLocale('ja');

        $rule = [
            'name' => [
                'label' => '氏名',
                'rule'  => [
                    ['CU', Valid::REQUIRED],
                    ['CU', Valid::MAX_LENGTH, 20],
                    ['CU', Valid::DEPENDENCE_CHAR],
                ]
            ]
        ];

        $request = Request::create('/');
        $request->request->set('name', '12345678901234567890orver');
        try {
            $valid_data = $request->validate('U', $rule, '/fallback/url');
        } catch (FallbackRedirectException $e) {
            $this->assertSame('/fallback/url', Reflector::get($e, 'fallback', null, true));
            $this->assertSame(['name' => '12345678901234567890orver'], Reflector::get($e, 'input', null, true));
            $this->assertSame(['name' => ['氏名は20文字以下で入力して下さい。']], Reflector::get($e, 'errors', null, true));
            throw $e;
        }
    }

    public function test_all()
    {
        $request = Request::create('/');
        $this->assertSame([], $request->all());

        $request->query->set('query', 1);
        $request->request->set('request', 2);
        $file = new UploadedFile(App::structure()->public('/assets/img/72x72.png'), 'OriginalName');
        $request->files->set('file', $file);
        $this->assertSame([
            'request' => 2,
            'query'   => 1,
            'file'    => $file,
        ], $request->all());
    }

    public function test_input()
    {
        $request = Request::create('/');
        $this->assertSame([], $request->all());

        $request->query->set('query', 1);
        $request->request->set('request', 2);
        $file = new UploadedFile(App::structure()->public('/assets/img/72x72.png'), 'OriginalName');
        $request->files->set('file', $file);
        $this->assertSame([
            'request' => 2,
            'query'   => 1,
        ], $request->input());
    }

    public function test_files()
    {
        $request = Request::create('/');
        $this->assertSame([], $request->all());

        $request->query->set('query', 1);
        $request->request->set('request', 2);
        $file = new UploadedFile(App::structure()->public('/assets/img/72x72.png'), 'OriginalName');
        $request->files->set('file', $file);
        $this->assertSame([
            'file' => $file,
        ], $request->files());
    }

    public function test_initialize()
    {
        $request = Request::create('/');
        $this->assertInstanceOf(FileBag::class, $request->files);

        $request->initialize();
        $this->assertInstanceOf(FileBag::class, $request->files);

        $file = new SymfonyUploadedFile(App::structure()->public('/assets/img/72x72.png'), 'OriginalName');
        $request->initialize([], [], [], [], ['file' => $file]);
        $this->assertInstanceOf(FileBag::class, $request->files);
        $this->assertInstanceOf(UploadedFile::class, $request->files->get('file'));
    }

    public function test_duplicate()
    {
        $request = Request::create('/');
        $this->assertInstanceOf(FileBag::class, $request->files);

        $copy = $request->duplicate();
        $this->assertInstanceOf(FileBag::class, $copy->files);

        $file = new SymfonyUploadedFile(App::structure()->public('/assets/img/72x72.png'), 'OriginalName');
        $copy = $request->duplicate(null, null, null, null, ['file' => $file]);
        $this->assertInstanceOf(FileBag::class, $copy->files);
        $this->assertInstanceOf(UploadedFile::class, $copy->files->get('file'));
    }

    public function test_getSession()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Request::getSession() method is unspported in Rebet. You can use Request::session() method to get the session instead.");

        $request = Request::create('/');
        $request->getSession();
    }

    public function test_setSession()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Request::setSession() method is unspported in Rebet. You can use Request::session() method to set the session instead.");

        $request = Request::create('/');
        $request->setSession(new SymfonySession());
    }

    public function test_setSessionFactory()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Request::setSessionFactory() method is unspported in Rebet. You can use Request::session() method to set the session factory instead.");

        $request = Request::create('/');
        $request->setSessionFactory(function () { return null; });
    }

    public function test_session_notSet()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Session has not been set");

        $request = Request::create('/');
        $request->session();
    }

    public function test_session()
    {
        $request = Request::create('/');
        $session = new Session();
        $this->assertInstanceOf(Request::class, $request->session($session));
        $this->assertInstanceOf(Session::class, $request->session());
        $this->assertSame($session, $request->session());

        $factory = function () use ($session) { return $session; };
        $this->assertInstanceOf(Request::class, $request->session($factory));
        $this->assertInstanceOf(Session::class, $request->session());
        $this->assertSame($session, $request->session());
    }

    public function test_bearerToken()
    {
        $request = Request::create('/');
        $this->assertNull($request->bearerToken());
        $request->setHeader('Authorization', 'Bearer token');
        $this->assertSame('token', $request->bearerToken());
    }

    public function dataGetRequestPaths() : array
    {
        return [
            ['/', '/', '', false],
            ['/path', '/path', '', false],
            ['/path', '/path', '', true],
            ['/path', '/path?key=value', '', false],
            ['/path', '/path?key=value', '', true],
            ['/prefix/path', '/prefix/path', '/prefix', false],
            ['/path', '/prefix/path', '/prefix', true],
            ['/prefix/prefix//path', '/prefix/prefix//path', '/prefix', false],
            ['/prefix//path', '/prefix/prefix//path', '/prefix', true],
            ['/a/prefix/path', '/a/prefix/path', '/prefix', false],
            ['/a/prefix/path', '/a/prefix/path', '/prefix', true],
        ];
    }

    /**
     * @dataProvider dataGetRequestPaths
     */
    public function test_getRequestPath($expect, $path, $prefix, bool $withoutPrefix)
    {
        $request = $this->createRequestMock($path, null, 'web', 'web', 'GET', $prefix);
        $this->assertSame($expect, $request->getRequestPath($withoutPrefix));
    }

    public function test_getRoutePrefix()
    {
        $request = Request::create('/prefix/foo');
        $this->assertSame('', $request->getRoutePrefix());

        Router::setCurrentChannel('web');
        Router::rules('web')->prefix('/prefix')->routing(function () {
            Router::get('/foo', function () { return 'foo'; });
        });

        $response = Router::handle($request);
        $this->assertSame('/prefix', $request->getRoutePrefix());

        $request  = Request::create('/prefix/bar');
        try {
            $response = Router::handle($request);
        } catch (\Exception $e) {
            $this->assertInstanceOf(RouteNotFoundException::class, $e);
            $this->assertSame('/prefix', $request->getRoutePrefix());
        }
    }

    public function test_getUserAgent()
    {
        $request = Request::create('/');
        $this->assertInstanceOf(UserAgent::class, $request->getUserAgent());
    }

    public function test_saveAs()
    {
        $request = $this->createRequestMock('/path/to/page');
        $request->query->set('query', 1);
        $request->request->set('request', 2);
        $file = new UploadedFile(App::structure()->public('/assets/img/72x72.png'), 'OriginalName');
        $request->files->set('files', $file);
        $request->saveAs('test');
        $session = $request->session();
        $this->assertSame('/path/to/page', $session->flash()->get('_request_test.uri'));
        $this->assertSame([
            'request' => 2,
            'query'   => 1,
        ], $session->flash()->get('_request_test.input'));
    }

    public function test_isSaved()
    {
        $request = $this->createRequestMock('/path/to/page');
        $this->assertFalse($request->isSaved('test'));
        $request->saveAs('test');
        $this->assertTrue($request->isSaved('test'));
    }

    public function test_replay()
    {
        $request  = $this->createRequestMock('/path/to/page');
        $response = $request->replay('test');
        $this->assertNull($response);

        $request->query->set('query', 1);
        $request->request->set('request', 2);
        $request->saveAs('test');
        $response = $request->replay('test');
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/path/to/page', $response->getTargetUrl());
        $this->assertSame([
            [
                ['/path/to/page'],
                [
                    'request' => 2,
                    'query'   => 1,
                ],
            ],
        ], $request->session()->flash()->peek('_inherit_input'));
    }

    public function test_restoreInheritData()
    {
        $request = $this->createRequestMock('/');
        $request->query->set('query', 1);
        $request->request->set('request', 2);
        $request->inheritInputTo('/user/*');
        $session = $request->session();

        $request = $this->createRequestMock('/login');
        $this->assertNull($request->input('query'));
        $this->assertNull($request->input('request'));
        $request->restoreInheritData();
        $this->assertNull($request->input('query'));
        $this->assertNull($request->input('request'));


        $request = $this->createRequestMock('/');
        $request->query->set('query', 1);
        $request->request->set('request', 2);
        $request->inheritInputTo('/user/*');
        $session = $request->session();

        $request = $this->createRequestMock('/user/input');
        $this->assertNull($request->input('query'));
        $this->assertNull($request->input('request'));
        $request->restoreInheritData();
        $this->assertSame(1, $request->input('query'));
        $this->assertSame(2, $request->input('request'));


        $request = $this->createRequestMock('/');
        $request->query->set('query', 1);
        $request->request->set('request', 2);
        $request->inheritInputTo('*');
        $request->request->set('request', 3);
        $request->inheritInputTo('/user/*');
        $session = $request->session();

        $request = $this->createRequestMock('/user/input');
        $this->assertNull($request->input('query'));
        $this->assertNull($request->input('request'));
        $request->restoreInheritData();
        $this->assertSame(1, $request->input('query'));
        $this->assertSame(3, $request->input('request'));
    }

    public function test_inheritInputTo()
    {
        $request = $this->createRequestMock('/');
        $request->query->set('query', 1);
        $request->request->set('request', 2);
        $request->inheritInputTo();
        $session = $request->session();
        $this->assertSame([
            [
                ['*'],
                [
                    'request' => 2,
                    'query'   => 1,
                ],
            ],
        ], $request->session()->flash()->peek('_inherit_input'));

        $request->query->set('query', 3);
        $request->request->set('request', 4);
        $request->inheritInputTo('/user/*');
        $session = $request->session();
        $this->assertSame([
            [
                ['*'],
                [
                    'request' => 2,
                    'query'   => 1,
                ],
            ],
            [
                ['/user/*'],
                [
                    'request' => 4,
                    'query'   => 3,
                ],
            ],
        ], $request->session()->flash()->peek('_inherit_input'));
    }

    public function test_expectsJson()
    {
        $request = Request::create('/');
        $this->assertFalse($request->isAjax());
        $this->assertFalse($request->isPjax());
        $this->assertFalse($request->acceptsAnyContentType());
        $this->assertFalse($request->wantsJson());
        $this->assertFalse($request->expectsJson());

        $request = Request::create('/');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $this->assertTrue($request->isAjax());
        $this->assertFalse($request->isPjax());
        $this->assertFalse($request->acceptsAnyContentType());
        $this->assertFalse($request->wantsJson());
        $this->assertFalse($request->expectsJson());

        $request = Request::create('/');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $request->setHeader('Accept', '*/*');
        $this->assertTrue($request->isAjax());
        $this->assertFalse($request->isPjax());
        $this->assertTrue($request->acceptsAnyContentType());
        $this->assertFalse($request->wantsJson());
        $this->assertTrue($request->expectsJson());

        $request = Request::create('/');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $request->setHeader('Accept', '*/*');
        $request->setHeader('X-PJAX', 'true');
        $this->assertTrue($request->isAjax());
        $this->assertTrue($request->isPjax());
        $this->assertTrue($request->acceptsAnyContentType());
        $this->assertFalse($request->wantsJson());
        $this->assertFalse($request->expectsJson());

        $request = Request::create('/');
        $request->setHeader('Accept', 'text/json');
        $this->assertFalse($request->isAjax());
        $this->assertFalse($request->isPjax());
        $this->assertFalse($request->acceptsAnyContentType());
        $this->assertTrue($request->wantsJson());
        $this->assertTrue($request->expectsJson());
    }

    public function test_wantsJson()
    {
        $request = Request::create('/');
        $request->setHeader('Accept', 'text/json');
        $this->assertTrue($request->wantsJson());

        $request = Request::create('/');
        $request->setHeader('Accept', 'application/problem+json');
        $this->assertTrue($request->wantsJson());

        $request = Request::create('/');
        $request->setHeader('Accept', 'text/html');
        $this->assertFalse($request->wantsJson());

        $request = Request::create('/');
        $request->setHeader('Accept', '*/*');
        $this->assertFalse($request->wantsJson());
    }

    public function test_isAjax()
    {
        $request = Request::create('/');
        $this->assertFalse($request->isAjax());
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $this->assertTrue($request->isAjax());
    }

    public function test_isPjax()
    {
        $request = Request::create('/');
        $this->assertFalse($request->isPjax());
        $request->setHeader('X-PJAX', 'true');
        $this->assertTrue($request->isPjax());

        $request = Request::create('/');
        $this->assertFalse($request->isPjax());
        $request->query->set('_pjax', 'true');
        $this->assertTrue($request->isPjax());
    }

    public function test_acceptsAnyContentType()
    {
        $request = Request::create('/');
        $request->setHeader('Accept', '*/*');
        $this->assertTrue($request->acceptsAnyContentType());

        $request = Request::create('/');
        $request->setHeader('Accept', '*');
        $this->assertTrue($request->acceptsAnyContentType());

        $request = Request::create('/');
        $request->headers->remove('Accept');
        $this->assertTrue($request->acceptsAnyContentType());

        $request = Request::create('/');
        $request->headers->remove('Accept');
        $request->setHeader('Accept', 'text/html');
        $this->assertFalse($request->acceptsAnyContentType());
    }

    public function test_getHeader()
    {
        $request = Request::create('/');
        $request->headers->add([
            'Content-Type' => 'text/html',
            'X-Test'       => 'This is test',
        ]);
        $this->assertSame('text/html', $request->getHeader('Content-Type'));
        $this->assertSame('This is test', $request->getHeader('X-Test'));
        $this->assertSame(null, $request->getHeader('X-Nothing'));
    }

    public function test_setHeader()
    {
        $request = Request::create('/');
        $this->assertSame(null, $request->getHeader('X-Test'));
        $request->setHeader('X-Test', 'a');
        $this->assertSame('a', $request->getHeader('X-Test'));
        $request->setHeader('X-Test', 'b');
        $this->assertSame('b', $request->getHeader('X-Test'));
        $request->setHeader('X-Test', 'c', false);
        $this->assertSame(['b', 'c'], $request->getHeader('X-Test'));
        $this->assertSame('b', $request->getHeader('X-Test', true));
    }
}
