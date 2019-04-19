<?php
namespace Rebet\Tests\Env;

use Rebet\Env\Dotenv;
use Rebet\Foundation\App;
use Rebet\Tests\RebetTestCase;
use Rebet\Tests\StderrCapture;

class DotenvTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @expectedException \Dotenv\Exception\InvalidPathException
     * @expectedExceptionMessage Unable to read the environment file at
     */
    public function test_init_notfound()
    {
       $dotenv = Dotenv::init(__DIR__);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_init()
    {
        $dotenv = Dotenv::init(App::path('/resources'), '.env.unittest');
        $this->assertSame('unittest', \getenv('APP_ENV'));
    }
}
