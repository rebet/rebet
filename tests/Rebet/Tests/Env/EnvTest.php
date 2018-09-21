<?php
namespace Rebet\Tests\Env;

use Rebet\Tests\RebetTestCase;
use Rebet\Env\Env;

use Rebet\Config\Config;
use Dotenv\Dotenv;

class EnvTest extends RebetTestCase
{
    public function setUp()
    {
        Config::clear();
    }
    
    /**
     * @runInSeparateProcess
     * @expectedException \Dotenv\Exception\InvalidPathException
     * @expectedExceptionMessage Unable to read the environment file at
     */
    public function test_loadDotenv_notfound()
    {
        $dotenv = Env::load(__DIR__);
        $this->fail("Never execute.");
    }
    
    /**
     * @runInSeparateProcess
     */
    public function test_loadDotenv()
    {
        $dotenv = Env::load(__DIR__.'/../../../', '.env.unittest');
        $this->assertSame('unittest', \getenv('APP_ENV'));
    }
}
