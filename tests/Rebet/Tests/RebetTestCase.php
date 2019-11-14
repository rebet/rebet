<?php
namespace Rebet\Tests;

use org\bovigo\vfs\vfsStream;


use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Rebet\Auth\Auth;
use Rebet\Auth\AuthUser;
use Rebet\Auth\Guard\SessionGuard;
use Rebet\Auth\Guard\TokenGuard;
use Rebet\Auth\Provider\ArrayProvider;
use Rebet\Common\Namespaces;
use Rebet\Common\Securities;
use Rebet\Common\System;
use Rebet\Config\Config;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Pagination\Storage\ArrayCursorStorage;
use Rebet\DateTime\DateTime;
use Rebet\Enum\Enum;
use Rebet\Event\Event;
use Rebet\Foundation\App;
use Rebet\Http\Cookie\Cookie;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Session\Session;
use Rebet\Http\Session\Storage\ArraySessionStorage;
use Rebet\Http\UploadedFile;
use Rebet\Log\Driver\Monolog\TestDriver;
use Rebet\Log\Log;
use Rebet\Routing\Route\ClosureRoute;
use Rebet\Routing\Router;
use Rebet\Tests\Mock\Address;
use Rebet\Tests\Mock\Entity\User;
use Rebet\Translation\Translator;
use Rebet\View\Engine\Twig\Node\CodeNode;
use Rebet\View\View;

/**
 * RebetTestCase Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetTestCase extends TestCase
{
    private static $start_at;

    protected function setUp()
    {
        System::initMock();
        Config::clear();
        App::setRoot(__DIR__.'/../../');
        App::initFrameworkConfig();
        $users = [
            ['user_id' => 1, 'role' => 'admin', 'name' => 'Admin'        , 'signin_id' => 'admin'        , 'email' => 'admin@rebet.local'        , 'password' => '$2y$04$68GZ8.IwFPFiVsae03fP7uMD76RYsEp9WunbITtrdRgvtJO1DGrim', 'api_token' => 'token_1', 'resigned_at' => null], // password: admin
            ['user_id' => 2, 'role' => 'user' , 'name' => 'User'         , 'signin_id' => 'user'         , 'email' => 'user@rebet.local'         , 'password' => '$2y$04$o9wMO8hXHHFpoNdLYRBtruWIUjPMU3Jqw9JAS0Oc7LOXiHFfn.7F2', 'api_token' => 'token_2', 'resigned_at' => null], // password: user
            ['user_id' => 3, 'role' => 'user' , 'name' => 'Resignd User' , 'signin_id' => 'user.resignd' , 'email' => 'user.resignd@rebet.local' , 'password' => '$2y$04$GwwjNndAojOi8uFu6xwFHe6L6Q/v6/7VynBatMHhCyfNt7momtiqK', 'api_token' => 'token_3', 'resigned_at' => DateTime::createDateTime('2001-01-01 12:34:56')], // password: user.resignd
            ['user_id' => 4, 'role' => 'user' , 'name' => 'Editable User', 'signin_id' => 'user.editable', 'email' => 'user.editable@rebet.local', 'password' => '$2y$10$3OTm0Ps5BeaYy5YZ619.4.gXwENPc4fVJBnMvBM5/5m/s0H6Nwg0O', 'api_token' => 'token_4', 'resigned_at' => null], // password: user.editable
        ];
        $auth_precondition = function ($user) { return !isset($user['resigned_at']); };
        Config::application([
            App::class => [
                'timezone'  => 'UTC',
                'locale'    => 'ja',
                'resources' => [
                    'i18n' => App::path('/resources/i18n'),
                ],
            ],
            Log::class => [
                'channels' => [
                    'default' => [
                        'driver' => TestDriver::class,
                    ],
                ],
            ],
            Namespaces::class => [
                'aliases' => [
                    '@mock'       => 'Rebet\\Tests\\Mock',
                    '@controller' => '@mock\\Controller',
                ],
            ],
            Session::class => [
                'storage' => ArraySessionStorage::class,
            ],
            Cursor::class => [
                'storage' => ArrayCursorStorage::class,
            ],
            Auth::class => [
                'authenticator' => [
                    'web' => [
                        'guard'    => SessionGuard::class,
                        'provider' => [ArrayProvider::class, $users, null, $auth_precondition],
                        'fallback' => '/user/signin', // url or function(Request):Response
                    ],
                    'api' => [
                        'guard'    => TokenGuard::class,
                        'provider' => [ArrayProvider::class, $users, null, $auth_precondition],
                        'fallback' => function (Request $request) { return Responder::problem(403); }, // url or function(Request):Response
                    ],
                ],
                'roles' => [
                    'admin'    => function (AuthUser $user) { return $user->role === 'admin'; },
                    'user'     => function (AuthUser $user) { return $user->role === 'user'; },
                    'editable' => function (AuthUser $user) { return $user->id === 4; },
                ],
                'policies' => [
                    User::class => [
                        '@before' => function (AuthUser $user, $target, string $action) { return $user->is('admin'); },
                        'update'  => function (AuthUser $user, User $target) { return $user->id === $target->user_id; },
                        'create'  => function (AuthUser $user) { return $user->is('editable'); },
                    ],
                    Address::class => [
                        'create'  => function (AuthUser $user, string $target, array $addresses) {
                            return !$user->isGuest() && count($addresses) < 5 ;
                        },
                    ]
                ]
            ],
            AuthUser::class => [
                'guest_aliases' => [
                    'role' => '@guest',
                ],
            ],
            Pager::class => [
                'resolver' => function (Pager $pager) { return $pager; }
            ]
        ]);
        Enum::clear();
        Event::clear();
        Cookie::clear();
        Request::clear();
        Session::clear();
        Router::clear();
        Translator::clear();
        CodeNode::clear();
        View::clear();
        StderrCapture::clear();
    }

    public static function setUpBeforeClass()
    {
        self::$start_at = microtime(true);
    }

    public static function tearDownAfterClass()
    {
        if (in_array('--debug', $_SERVER['argv'], true)) {
            $spend = (microtime(true) - self::$start_at);
            printf(" ... Time: %f [ms] - ".static::class."\n", $spend * 1000);
        }
    }

    protected function vfs(array $structure) : vfsStreamDirectory
    {
        return vfsStream::setup('root', null, $structure);
    }

    protected function assertSameStderr($expects, callable $test)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        StderrCapture::clearStart();
        $test();
        $actual = StderrCapture::stopGetClear();
        foreach ($expects as $expect) {
            $this->assertSame($expect, $actual);
        }
    }

    protected function assertContainsStderr($expects, callable $test)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        StderrCapture::clearStart();
        $test();
        $actual = StderrCapture::stopGetClear();
        foreach ($expects as $expect) {
            $this->assertContains($expect, $actual);
        }
    }

    protected function assertRegExpStderr($expects, callable $test)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        StderrCapture::clearStart();
        $test();
        $actual = StderrCapture::stopGetClear();
        foreach ($expects as $expect) {
            $this->assertRegExp($expect, $actual);
        }
    }

    protected function assertSameOutbuffer($expects, callable $test)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        \ob_start();
        $test();
        $actual = \ob_get_clean();
        foreach ($expects as $expect) {
            $this->assertSame($expect, $actual);
        }
    }

    protected function assertContainsOutbuffer($expects, callable $test)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        \ob_start();
        $test();
        $actual = \ob_get_clean();
        foreach ($expects as $expect) {
            $this->assertContains($expect, $actual);
        }
    }

    protected function assertRegExpOutbuufer($expects, callable $test)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        \ob_start();
        $test();
        $actual = \ob_get_clean();
        foreach ($expects as $expect) {
            $this->assertRegExp($expect, $actual);
        }
    }

    protected function _randomCode(int $min_length, ?int $max_length = null, string $chars = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890") : string
    {
        if ($max_length == null) {
            $max_length = $min_length;
        }
        return Securities::randomCode(mt_rand($min_length, $max_length), $chars);
    }

    protected function createRequestMock($path, $roles = null, $channel = 'web', $method = 'GET', $prefix = '', $route = null) : Request
    {
        Router::setCurrentChannel($channel);
        Router::activatePrefix($prefix);
        $session = Session::current() ?? new Session();
        $session->start();
        $request = Request::create($path, $method);
        $request->session($session);
        $request->route = $route ?? new ClosureRoute([], $path, function () use ($channel) { return $channel === 'api' ? ['OK'] : 'OK' ; });
        $request->route->roles(...((array)$roles));
        $request->route->prefix = $prefix;
        return $request;
    }

    protected function createJsonRequestMock($path, $roles = null, $channel = 'api', $method = 'GET', $prefix = '') : Request
    {
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

    protected function signin(Request $request = null, string $signin_id = 'user@rebet.local', string $password = 'user') : Request
    {
        $request = $request ?? $this->createRequestMock('/');
        Auth::signin($request, Auth::attempt($request, $signin_id, $password), '/');
        return $request;
    }

    protected function signout(Request $request = null) : void
    {
        Auth::signout($request ?? $this->createRequestMock('/'));
    }

    protected function setProperty($target, string $name, $value)
    {
        $class = is_string($target) ? $target : get_class($target) ;
        $rp    = new \ReflectionProperty($class, $name);
        $rp->setAccessible(true);
        $rp->setValue(is_string($target) ? null : $target, $value);
    }
}
