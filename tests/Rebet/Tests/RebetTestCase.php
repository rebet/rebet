<?php
namespace Rebet\Tests;

use PHPUnit\Framework\TestCase;


use Rebet\Auth\Auth;
use Rebet\Auth\AuthUser;
use Rebet\Auth\Guard\SessionGuard;
use Rebet\Auth\Guard\TokenGuard;
use Rebet\Auth\Provider\ArrayProvider;
use Rebet\Common\Securities;
use Rebet\Common\System;
use Rebet\Config\Config;
use Rebet\DateTime\DateTime;
use Rebet\Enum\Enum;
use Rebet\Event\Event;
use Rebet\Foundation\App;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Http\Session\Session;
use Rebet\Routing\Route\ClosureRoute;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * RebetTestCase Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetTestCase extends TestCase
{
    private static $start_at;
    
    public function setUp()
    {
        System::initMock();
        Config::clear();
        App::initFrameworkConfig();
        App::setRoot(__DIR__.'/../../');
        $users = [
            ['id' => 1, 'role' => 'admin', 'name' => 'Admin'       , 'signin_id' => 'admin'       , 'email' => 'admin@rebet.com'        , 'password' => '$2y$04$68GZ8.IwFPFiVsae03fP7uMD76RYsEp9WunbITtrdRgvtJO1DGrim', 'api_token' => 'token_1', 'resigned_at' => null], // password: admin
            ['id' => 2, 'role' => 'user' , 'name' => 'User'        , 'signin_id' => 'user'        , 'email' => 'user@rebet.com'         , 'password' => '$2y$04$o9wMO8hXHHFpoNdLYRBtruWIUjPMU3Jqw9JAS0Oc7LOXiHFfn.7F2', 'api_token' => 'token_2', 'resigned_at' => null], // password: user
            ['id' => 3, 'role' => 'user' , 'name' => 'Resignd User', 'signin_id' => 'user.resignd', 'email' => 'user.resignd@rebet.com' , 'password' => '$2y$04$GwwjNndAojOi8uFu6xwFHe6L6Q/v6/7VynBatMHhCyfNt7momtiqK', 'api_token' => 'token_3', 'resigned_at' => DateTime::createDateTime('2001-01-01 12:34:56')], // password: user.resignd
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
            Session::class => [
                'storage' => MockArraySessionStorage::class,
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
                    'admin' => function (AuthUser $user) { return $user->role === 'admin'; },
                    'user'  => function (AuthUser $user) { return $user->role === 'user'; },
                ],
            ],
        ]);
        Enum::clear();
        Event::clear();
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
            printf(" ... Time: %f [ms]\n", $spend * 1000);
        }
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

    protected function createRequestMock($path, $roles = null, $channel = 'web') : Request
    {
        $session = new Session();
        $session->start();
        $request = Request::create($path);
        $request->setRebetSession($session);
        $request->route = new ClosureRoute([], $path, function () { return 'TEST'; });
        $request->route->roles(...((array)$roles));
        $request->channel = $channel;
        return $request;
    }
}
