<?php
namespace Rebet\Tests;

use App\AppStructure;
use App\Http\AppWebKernel;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Rebet\Application\App;
use Rebet\Auth\Auth;
use Rebet\Http\Request;
use Rebet\Http\Session\Session;
use Rebet\Http\UploadedFile;
use Rebet\Routing\Route\ClosureRoute;
use Rebet\Routing\Router;
use Rebet\Tools\Testable\System;
use Rebet\Tools\Testable\TestHelper;
use Rebet\Tools\Utility\Securities;

/**
 * RebetTestCase Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetTestCase extends TestCase
{
    use TestHelper;

    public static function setUpBeforeClass() : void
    {
        static::setUpWorkingDir((new AppStructure(__DIR__.'/../../../'))->path('/work'));
    }

    protected function setUp() : void
    {
        App::clear();
        System::testing(true);
        App::init(new AppWebKernel(new AppStructure(__DIR__.'/../../../app')));
    }

    // protected function assertPreConditions() {}

    // protected function assertPostConditions() {}

    // protected function tearDown() : void {}

    // protected function onNotSuccessfulTest(Throwable $t) {}

    public static function tearDownAfterClass() : void
    {
        static::tearDownWorkingDir();
        // echo static::memory();
    }

    protected function vfs(array $structure) : vfsStreamDirectory
    {
        return vfsStream::setup('root', null, $structure);
    }

    protected function _randomCode(int $min_length, ?int $max_length = null, string $chars = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890") : string
    {
        if ($max_length == null) {
            $max_length = $min_length;
        }
        return Securities::randomCode(mt_rand($min_length, $max_length), $chars);
    }

    protected function createRequestMock($path, $roles = null, $channel = 'web', $guard = 'web', $method = 'GET', $prefix = '', $route = null) : Request
    {
        Auth::clear();
        Router::setCurrentChannel($channel);
        Router::activatePrefix($prefix);
        $session = Session::current() ?? new Session();
        $session->start();
        $request = Request::create($path, $method);
        $request->session($session);
        $request->route = $route ?? new ClosureRoute([], $path, function () use ($channel) { return $channel === 'api' ? ['OK'] : 'OK' ; });
        if ($guard) {
            $request->route->guard($guard);
        }
        $request->route->roles(...((array)$roles));
        $request->route->prefix = $prefix;
        return $request;
    }

    protected function createJsonRequestMock($path, $roles = null, $channel = 'api', $method = 'GET', $prefix = '') : Request
    {
        Auth::clear();
        Router::setCurrentChannel($channel);
        $session = Session::current() ?? new Session();
        $session->start();
        $request = Request::create($path, $method);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->headers->set('Accept', '*/*');
        $request->session($session);
        $request->route = new ClosureRoute([], $path, function () use ($channel) { return $channel === 'api' ? ['OK'] : 'OK' ; });
        $request->route->roles(...((array)$roles));
        $request->route->prefix = $prefix;
        return $request;
    }

    protected function createUploadedFileMock(string $name, string $mime_type)
    {
        $stub = $this->createMock(UploadedFile::class);
        $stub->expects($this->any())->method('getClientOriginalName')->willReturn($name);
        $stub->expects($this->any())->method('getMimeType')->willReturn($mime_type);
        return $stub;
    }

    protected function signin(Request $request = null, string $signin_id = 'user@rebet.local', string $password = 'user', string $fallback = '/user/signin', string $goto = '/') : Request
    {
        $request = $request ?? $this->createRequestMock('/');
        Auth::signin($request, Auth::attempt($request, $signin_id, $password), $fallback, $goto);
        return $request;
    }

    protected function signout(Request $request = null) : void
    {
        Auth::signout($request ?? $this->createRequestMock('/'));
    }
}
