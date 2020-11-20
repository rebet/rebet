<?php
namespace Rebet\Tests;

use org\bovigo\vfs\vfsStream;


use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Rebet\Application\App;
use Rebet\Application\Structure;
use Rebet\Auth\Auth;
use Rebet\Auth\AuthUser;
use Rebet\Auth\Guard\SessionGuard;
use Rebet\Auth\Guard\TokenGuard;
use Rebet\Auth\Provider\ArrayProvider;
use Rebet\Cache\Adapter\ArrayAdapter;
use Rebet\Cache\Cache;
use Rebet\Database\Pagination\Cursor;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Pagination\Storage\ArrayCursorStorage;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Session\Session;
use Rebet\Http\Session\Storage\ArraySessionStorage;
use Rebet\Http\UploadedFile;
use Rebet\Log\Driver\Monolog\TestDriver;
use Rebet\Log\Log;
use Rebet\Log\LogLevel;
use Rebet\Mail\Mail;
use Rebet\Routing\Route\ClosureRoute;
use Rebet\Routing\Router;
use Rebet\Tests\Mock\Address;
use Rebet\Tests\Mock\AppHttpKernel;
use Rebet\Tests\Mock\Entity\User;
use Rebet\Tools\Config\Config;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Testable\System;
use Rebet\Tools\Utility\Files;
use Rebet\Tools\Utility\Namespaces;
use Rebet\Tools\Utility\Path;
use Rebet\Tools\Utility\Securities;

/**
 * RebetTestCase Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetTestCase extends TestCase
{
    protected static $original_cwd;
    protected static $work_dir;
    protected static $unittest_cwd;

    public static function setUpBeforeClass() : void
    {
        self::$original_cwd = Path::normalize(getcwd());
        self::$work_dir     = (new Structure(__DIR__.'/../../../'))->path('/work');
        self::$unittest_cwd = self::$work_dir.'/'.getmypid();
        if (!file_exists(self::$unittest_cwd)) {
            mkdir(self::$unittest_cwd, 0777, true);
        }
        chdir(self::$unittest_cwd);
    }

    protected function setUp() : void
    {
        App::clear();
        System::testing(true);
        App::init(new AppHttpKernel(new Structure(__DIR__.'/../../../application')));
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
            ],
            Log::class => [
                'unittest' => true,
                'channels' => [
                    'web' => [
                        'driver' => [
                            '@factory' => TestDriver::class,
                            'name'     => 'web',
                            'level'    => LogLevel::DEBUG,
                        ],
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
                        'provider' => [
                            '@factory'     => ArrayProvider::class,
                            'users'        => $users,
                            'precondition' => $auth_precondition
                        ],
                        'fallback' => '/user/signin', // url or function(Request):Response
                    ],
                    'api' => [
                        'guard'    => TokenGuard::class,
                        'provider' => [
                            '@factory'     => ArrayProvider::class,
                            'users'        => $users,
                            'precondition' => $auth_precondition
                        ],
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
            ],
            Cache::class => [
                'stores=' => [
                    'array' => [
                        'adapter' => ArrayAdapter::class,
                    ],
                ],
                'default_store' => 'array',
            ],
            Mail::class => [
                'development' => true,
                'unittest'    => true,
            ],
        ]);
        StderrCapture::clear();
    }

    // protected function assertPreConditions() {}

    // protected function assertPostConditions() {}

    // protected function tearDown() : void {}

    // protected function onNotSuccessfulTest(Throwable $t) {}

    public static function tearDownAfterClass() : void
    {
        chdir(self::$original_cwd);
        Files::removeDir(self::$unittest_cwd);
    }

    protected function vfs(array $structure) : vfsStreamDirectory
    {
        return vfsStream::setup('root', null, $structure);
    }

    protected function assertSameStderr(string $expect, callable $test)
    {
        StderrCapture::clearStart();
        $test();
        $actual = StderrCapture::stopGetClear();
        $this->assertSameString($expect, $actual);
    }

    protected function assertNotSameStderr($expects, callable $test)
    {
        StderrCapture::clearStart();
        $test();
        $actual = StderrCapture::stopGetClear();
        $this->assertNotSameString($expects, $actual);
    }

    protected function assertContainsStderr($expects, callable $test)
    {
        StderrCapture::clearStart();
        $test();
        $actual = StderrCapture::stopGetClear();
        $this->assertContainsString($expects, $actual);
    }

    protected function assertNotContainsStderr($expects, callable $test)
    {
        StderrCapture::clearStart();
        $test();
        $actual = StderrCapture::stopGetClear();
        $this->assertNotContainsString($expects, $actual);
    }

    protected function assertRegExpStderr($expects, callable $test)
    {
        StderrCapture::clearStart();
        $test();
        $actual = StderrCapture::stopGetClear();
        $this->assertRegExpString($expects, $actual);
    }

    protected function assertNotRegExpStderr($expects, callable $test)
    {
        StderrCapture::clearStart();
        $test();
        $actual = StderrCapture::stopGetClear();
        $this->assertNotRegExpString($expects, $actual);
    }

    protected function assertSameStdout(string $expect, callable $test)
    {
        \ob_start();
        $test();
        $actual = \ob_get_clean();
        $this->assertSameString($expect, $actual);
    }

    protected function assertNotSameStdout($expects, callable $test)
    {
        \ob_start();
        $test();
        $actual = \ob_get_clean();
        $this->assertNotSameString($expects, $actual);
    }

    protected function assertContainsStdout($expects, callable $test)
    {
        \ob_start();
        $test();
        $actual = \ob_get_clean();
        $this->assertContainsString($expects, $actual);
    }

    protected function assertNotContainsStdout($expects, callable $test)
    {
        \ob_start();
        $test();
        $actual = \ob_get_clean();
        $this->assertNotContainsString($expects, $actual);
    }

    protected function assertRegExpStdout($expects, callable $test)
    {
        \ob_start();
        $test();
        $actual = \ob_get_clean();
        $this->assertRegExpString($expects, $actual);
    }

    protected function assertNotRegExpStdout($expects, callable $test)
    {
        \ob_start();
        $test();
        $actual = \ob_get_clean();
        $this->assertNotRegExpString($expects, $actual);
    }

    protected function assertSameString(string $expect, string $actual)
    {
        $this->assertSame($expect, $actual);
    }

    protected function assertNotSameString($expects, string $actual)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            $this->assertNotSame($expect, $actual);
        }
    }

    protected function assertContainsString($expects, string $actual)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            $this->assertStringContainsString($expect, $actual);
        }
    }

    protected function assertNotContainsString($expects, string $actual)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            $this->assertStringNotContainsString($expect, $actual);
        }
    }

    protected function assertRegExpString($expects, string $actual)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            $this->assertMatchesRegularExpression($expect, $actual);
        }
    }

    protected function assertNotRegExpString($expects, string $actual)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            $this->assertDoesNotMatchRegularExpression($expect, $actual);
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

    protected function inspect($target, string $name)
    {
        $class = is_string($target) ? $target : get_class($target) ;
        $rp    = new \ReflectionProperty($class, $name);
        $rp->setAccessible(true);
        return $rp->getValue($target);
    }

    protected function inject($target, string $name, $value)
    {
        Reflector::set($target, $name, $value, true);
        // $class = is_string($target) ? $target : get_class($target) ;
        // $rp    = new \ReflectionProperty($class, $name);
        // $rp->setAccessible(true);
        // $rp->setValue(is_string($target) ? null : $target, $value);
    }

    protected function invoke($object, string $method, array $args = [], bool $type_convert = false)
    {
        return Reflector::invoke($object, $method, $args, true, $type_convert);
    }

    protected function isWindows() : bool
    {
        return PHP_OS === 'WIN32' || PHP_OS === 'WINNT';
    }
}
