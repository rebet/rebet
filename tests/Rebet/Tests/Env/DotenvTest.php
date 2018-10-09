<?php
namespace Rebet\Tests\Env;

use Rebet\Tests\RebetTestCase;
use Rebet\Env\Dotenv;

use Rebet\Config\Config;

class DotenvTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
    }
    
    /**
     * @runInSeparateProcess
     * @expectedException \Dotenv\Exception\InvalidPathException
     * @expectedExceptionMessage Unable to read the environment file at
     */
    public function test_init_notfound()
    {
        $dotenv = Dotenv::init(__DIR__);
        $this->fail("Never execute.");
    }
    
    /**
     * @runInSeparateProcess
     */
    public function test_init()
    {
        $dotenv = Dotenv::init(__DIR__.'/../../../', '.env.unittest');
        $this->assertSame('unittest', \getenv('APP_ENV'));
    }
}