<?php
namespace Rebet\Tests\Env;

use Rebet\Application\App;
use Rebet\Env\Dotenv;
use Rebet\Tests\RebetTestCase;

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
        $dotenv = Dotenv::init(App::structure()->env(), '.env');
        $this->assertSame('unittest', \getenv('APP_ENV'));
    }
}
