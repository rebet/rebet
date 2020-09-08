<?php
namespace Rebet\Tests\Env;

use Dotenv\Exception\InvalidPathException;
use Rebet\Application\App;
use Rebet\Env\Dotenv;
use Rebet\Tests\RebetTestCase;

class DotenvTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_init_notfound()
    {
        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage("Unable to read any of the environment file(s) at");

        Dotenv::load(__DIR__);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_init()
    {
        Dotenv::load(App::structure()->env(), '.env');
        $this->assertSame('unittest', \getenv('APP_ENV'));
    }
}
