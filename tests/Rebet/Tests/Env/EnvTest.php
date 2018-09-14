<?php
namespace Rebet\Tests\Env;

use Rebet\Tests\RebetTestCase;
use Rebet\Env\Env;

use Rebet\Config\Config;
use Dotenv\Dotenv;

class EnvTest extends RebetTestCase {

    public function setUp() {
        Config::clear();
    }
    
    /**
     * @runInSeparateProcess
     * @expectedException \Dotenv\Exception\InvalidPathException
     * @expectedExceptionMessage Unable to read the environment file at
     */
    public function test_loadDotenv_notfound() {
        $dotenv = Env::load(__DIR__);
        $this->fail("Never execute.");
    }
    
    /**
     * @runInSeparateProcess
     */
    public function test_loadDotenv() {
        $dotenv = Env::load(__DIR__.'/../../../', '.env.unittest');
        $this->assertSame('unittest', \getenv('APP_ENV'));
    }

    /**
     * @runInSeparateProcess
     * @expectedException Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: UNDEFINED is missing.
     */
    public function test_loadDotenv_validate_array() {
        Config::framework([
            Env::class => [
                'dotenv_validate' => ['APP_ENV', 'UNDEFINED' ]
            ]
        ]);

        $dotenv = Env::load(__DIR__.'/../../../', '.env.unittest');
        $this->fail("Never execute.");
    }
    
    /**
     * @runInSeparateProcess
     * @expectedException Dotenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: APP_ENV is not an integer.
     */
    public function test_loadDotenv_validate_closure() {
        Config::framework([
            Env::class => [
                'dotenv_validate' => function(Dotenv $dotenv) {
                    $dotenv->required(['APP_ENV'])->isInteger();
                }
            ]
        ]);

        $dotenv = Env::load(__DIR__.'/../../../', '.env.unittest');
        $this->fail("Never execute.");
    }
}
