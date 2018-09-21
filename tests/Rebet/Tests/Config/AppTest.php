<?php
namespace Rebet\Tests\Config;

use Rebet\Env\Env;
use Rebet\Tests\RebetTestCase;
use Rebet\Config\Config;
use Rebet\Config\App;

class AppTest extends RebetTestCase
{
    public function setUp()
    {
        Config::clear();
    }

    public function test_getRoot()
    {
        App::setRoot('/var/www/app');
        $this->assertSame('/var/www/app', App::getRoot());

        App::setRoot('c:\\var\\www\\app');
        $this->assertSame('c:/var/www/app', App::getRoot());

        App::setRoot('vfs://var/www/app');
        $this->assertSame('vfs://var/www/app', App::getRoot());
    }

    public function test_setRoot()
    {
        App::setRoot('/var/www/app');
        $this->assertSame('/var/www/app', App::getRoot());

        App::setRoot('/var/www/app2');
        $this->assertSame('/var/www/app2', App::getRoot());

        App::setRoot('vfs://var/www/app');
        $this->assertSame('vfs://var/www/app', App::getRoot());
    }

    public function test_path()
    {
        App::setRoot('/var/www/app');
        $this->assertSame('/var/www/app/var/logs', App::path('/var/logs'));
        $this->assertSame('/var/www/app/var/logs', App::path('var/logs'));
        $this->assertSame('/var/www/.env', App::path('/../.env'));
        $this->assertSame('/var/www/.env', App::path('../.env'));

        App::setRoot('c:\\var\\www\\app\\');
        $this->assertSame('c:/var/www/app/var/logs', App::path('/var/logs'));
        $this->assertSame('c:/var/www/.env', App::path('../.env'));

        App::setRoot('file:\\\\var\\www\\app');
        $this->assertSame('file://var/www/app/var/logs', App::path('/var/logs'));
        $this->assertSame('file://var/www/.env', App::path('../.env'));

        App::setRoot('file:\\\\c:\\var\\www\\app\\');
        $this->assertSame('file://c:/var/www/app/var/logs', App::path('/var/logs'));
        $this->assertSame('file://c:/var/www/.env', App::path('../.env'));
    }

    public function test_getLocale()
    {
        $this->assertSame('ja', App::getLocale());

        Config::application([
            App::class => [
                'locale' => 'en',
            ],
        ]);

        $this->assertSame('en', App::getLocale());
    }

    public function test_setLocale()
    {
        $this->assertSame('ja', App::getLocale());
        App::setLocale('en');
        $this->assertSame('en', App::getLocale());
    }

    public function test_localeIn()
    {
        $this->assertTrue(App::localeIn('ja'));
        $this->assertFalse(App::localeIn('en', 'de'));
    }

    public function test_getEnv()
    {
        $this->assertSame('development', App::getEnv());

        Config::application([
            App::class => [
                'env' => 'production',
            ],
        ]);

        $this->assertSame('production', App::getEnv());
    }
    
    /**
     * @runInSeparateProcess
     */
    public function test_getEnv_envLoad()
    {
        $dotenv = Env::load(__DIR__.'/../../../', '.env.unittest');
        $this->assertSame('unittest', App::getEnv());
    }

    public function test_setEnv()
    {
        $this->assertSame('development', App::getEnv());
        App::setEnv('production');
        $this->assertSame('production', App::getEnv());
    }

    public function test_envIn()
    {
        $this->assertTrue(App::envIn('development', 'local'));
        $this->assertFalse(App::envIn('production', 'staging'));
    }

    public function test_getTimezone()
    {
        Config::framework([
            App::class => [
                'timezone' => 'UTC',
            ],
        ]);

        $this->assertSame('UTC', App::getTimezone());

        Config::application([
            App::class => [
                'timezone' => 'Asia/Tokyo',
            ],
        ]);

        $this->assertSame('Asia/Tokyo', App::getTimezone());
    }

    public function test_setTimezone()
    {
        App::setTimezone('Asia/Tokyo');
        $this->assertSame('Asia/Tokyo', App::getTimezone());
    }
}
