<?php
namespace Rebet\Tests;

use App\Http\AppHttpKernel;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Rebet\Application\App;
use Rebet\Application\Structure;
use Rebet\Auth\Auth;
use Rebet\Http\Request;
use Rebet\Http\Session\Session;
use Rebet\Http\UploadedFile;
use Rebet\Routing\Route\ClosureRoute;
use Rebet\Routing\Router;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Testable\System;
use Rebet\Tools\Utility\Files;
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

        // $current = number_format(memory_get_usage() / 1048576, 2);
        // $peak    = number_format(memory_get_peak_usage() / 1048576, 2);
        // echo "\n>> Memory {$current} MB / Peak Memory {$peak} MB";
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

    protected function assertwildcardString($expects, string $actual)
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            $this->assertTrue(fnmatch($expect, $actual), "Wildcard miss match: expect \"{$expect}\" but actual \"$actual\".");
        }
    }

    protected function assertRegExpEach(array $expects, array $actuals)
    {
        $this->assertSame(count($expects), count($actuals));
        foreach ($expects as $i => $expect) {
            $this->assertMatchesRegularExpression($expect, $actuals[$i]);
        }
    }

    protected function assertWildcardEach(array $expects, array $actuals)
    {
        $this->assertSame(count($expects), count($actuals));
        foreach ($expects as $i => $expect) {
            $this->assertTrue(fnmatch($expect, $actuals[$i]), "Wildcard miss match: expect \"{$expect}\" but actual \"$actuals[$i]\".");
        }
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
