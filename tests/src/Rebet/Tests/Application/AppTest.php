<?php
namespace Rebet\Tests\Application;

use App\Stub\KernelStub;
use Rebet\Application\App;
use Rebet\Application\Structure;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;

class AppTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
    }

    public function test_init()
    {
        $kernel = App::init(new KernelStub(new Structure('/var/www/app'), 'web'));
        $this->assertSame($kernel, App::kernel());
        $this->assertSame('web', App::channel());
    }

    public function test_root()
    {
        App::init(new KernelStub(new Structure('/var/www/app'), 'web'));
        $this->assertSame('/var/www/app', App::root());

        App::init(new KernelStub(new Structure('c:\\var\\www\\app'), 'web'));
        $this->assertSame('c:/var/www/app', App::root());

        App::init(new KernelStub(new Structure('vfs://var/www/app'), 'web'));
        $this->assertSame('vfs://var/www/app', App::root());
    }

    public function test_path()
    {
        App::init(new KernelStub(new Structure('/var/www/app'), 'web'));
        $this->assertSame('/var/www/app/var/logs', App::path('/var/logs'));
        $this->assertSame('/var/www/app/var/logs', App::path('var/logs'));
        $this->assertSame('/var/www/.env', App::path('/../.env'));
        $this->assertSame('/var/www/.env', App::path('../.env'));

        App::init(new KernelStub(new Structure('c:\\var\\www\\app\\'), 'web'));
        $this->assertSame('c:/var/www/app/var/logs', App::path('/var/logs'));
        $this->assertSame('c:/var/www/.env', App::path('../.env'));

        App::init(new KernelStub(new Structure('file:\\\\var\\www\\app'), 'web'));
        $this->assertSame('file://var/www/app/var/logs', App::path('/var/logs'));
        $this->assertSame('file://var/www/.env', App::path('../.env'));

        App::init(new KernelStub(new Structure('file:\\\\c:\\var\\www\\app\\'), 'web'));
        $this->assertSame('file://c:/var/www/app/var/logs', App::path('/var/logs'));
        $this->assertSame('file://c:/var/www/.env', App::path('../.env'));
    }

    public function test_getLocale()
    {
        $this->assertSame('en', App::getLocale());

        Config::runtime([
            App::class => [
                'locale' => 'ja',
            ],
        ]);

        $this->assertSame('ja', App::getLocale());
    }

    public function test_getFallbackLocale()
    {
        $this->assertSame('en', App::getFallbackLocale());

        Config::runtime([
            App::class => [
                'fallback_locale' => 'ja',
            ],
        ]);

        $this->assertSame('ja', App::getFallbackLocale());
    }

    public function test_setLocale()
    {
        $this->assertSame('en', App::getLocale());
        $this->assertSame('en', App::getFallbackLocale());
        App::setLocale('de');
        $this->assertSame('de', App::getLocale());
        $this->assertSame('en', App::getFallbackLocale());
        App::setLocale('ja', 'ja');
        $this->assertSame('ja', App::getLocale());
        $this->assertSame('ja', App::getFallbackLocale());
    }

    public function test_localeIn()
    {
        $this->assertTrue(App::localeIn('en'));
        $this->assertFalse(App::localeIn('ja', 'de'));
    }

    public function test_env()
    {
        $this->assertSame('unittest', App::env());
        \putenv("APP_ENV=production");
        $this->assertSame('production', App::env());
    }

    public function test_envIn()
    {
        $this->assertTrue(App::envIn('unittest', 'local'));
        $this->assertFalse(App::envIn('production', 'staging'));
    }

    public function test_channel()
    {
        $this->assertSame('web', App::channel());
    }

    public function test_ChannelIn()
    {
        $this->assertTrue(App::ChannelIn('web'));
        $this->assertFalse(App::ChannelIn('console', 'api'));
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

        $this->inject(App::class, 'kernel.channel', 'console');
        \putenv("APP_ENV=unittest");
        $this->assertSame('console@unittest', App::when($case)->get());

        $this->inject(App::class, 'kernel.channel', 'console');
        \putenv("APP_ENV=development");
        $this->assertSame('console', App::when($case)->get());

        $this->inject(App::class, 'kernel.channel', 'api');
        \putenv("APP_ENV=unittest");
        $this->assertSame('unittest', App::when($case)->get());

        $this->inject(App::class, 'kernel.channel', 'web');
        \putenv("APP_ENV=local");
        $this->assertSame('web@local', App::when($case)->get());

        $this->inject(App::class, 'kernel.channel', 'api');
        \putenv("APP_ENV=development");
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
