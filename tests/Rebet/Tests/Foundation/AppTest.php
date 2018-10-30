<?php
namespace Rebet\Tests\Foundation;

use Rebet\Env\Dotenv;
use Rebet\Tests\RebetTestCase;
use Rebet\Config\Config;
use Rebet\Foundation\App;

class AppTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
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

        Config::runtime([
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
        $dotenv = Dotenv::init(__DIR__.'/../../../resources', '.env.unittest');
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

    public function test_getSurface()
    {
        Config::application([
            App::class => [
                'surface' => 'console',
            ],
        ]);
        $this->assertSame('console', App::getSurface());
    }

    public function test_setSurface()
    {
        App::setSurface('console');
        $this->assertSame('console', App::getSurface());
    }

    public function test_SurfaceIn()
    {
        App::setSurface('console');
        $this->assertTrue(App::SurfaceIn('console'));
        $this->assertFalse(App::SurfaceIn('web', 'api'));
    }

    public function test_getEntryPoint()
    {
        Config::application([
            App::class => [
                'entry_point' => 'unittest',
            ],
        ]);
        $this->assertSame('unittest', App::getEntryPoint());
    }

    public function test_setEntryPoint()
    {
        App::setEntryPoint('unittest');
        $this->assertSame('unittest', App::getEntryPoint());
    }

    public function test_when()
    {
        $case = [
            'console@unittest' => 'console@unittest',
            'console'          => 'console',
            'unittest'         => 'unittest',
            'web@local'        => 'web@local',
            'api@production'   => 'api@production',
            'default'          => 'default',
        ];

        App::setSurface('console');
        App::setEnv('unittest');
        $this->assertSame('console@unittest', App::when($case)->get());

        App::setSurface('console');
        App::setEnv('development');
        $this->assertSame('console', App::when($case)->get());

        App::setSurface('api');
        App::setEnv('unittest');
        $this->assertSame('unittest', App::when($case)->get());

        App::setSurface('web');
        App::setEnv('local');
        $this->assertSame('web@local', App::when($case)->get());

        App::setSurface('api');
        App::setEnv('development');
        $this->assertSame('default', App::when($case)->get());
    }

    public function test_getTimezone()
    {
        Config::clear();
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
