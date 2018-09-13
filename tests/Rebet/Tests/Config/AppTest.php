<?php
namespace Rebet\Tests\Config;

use Rebet\Tests\RebetTestCase;
use Rebet\Config\Config;
use Rebet\Config\App;

class AppTest extends RebetTestCase {

    public function setUp() {
        Config::clear();
    }

    public function test_getLocale() {
        $this->assertSame('ja', App::getLocale());

        Config::application([
            App::class => [
                'locale' => 'en',
            ],
        ]);

        $this->assertSame('en', App::getLocale());
    }

    public function test_setLocale() {
        $this->assertSame('ja', App::getLocale());
        App::setLocale('en');
        $this->assertSame('en', App::getLocale());
    }

    public function test_locale() {
        $this->assertTrue(App::locale('ja'));
        $this->assertFalse(App::locale('en','de'));
    }

    public function test_getEnv() {
        $this->assertSame('development', App::getEnv());

        Config::application([
            App::class => [
                'env' => 'production',
            ],
        ]);

        $this->assertSame('production', App::getEnv());
    }

    public function test_setEnv() {
        $this->assertSame('development', App::getEnv());
        App::setEnv('production');
        $this->assertSame('production', App::getEnv());
    }

    public function test_env() {
        $this->assertTrue(App::env('development', 'local'));
        $this->assertFalse(App::env('production', 'staging'));
    }

    public function test_getTimezone() {
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

    public function test_setTimezone() {
        App::setTimezone('Asia/Tokyo');
        $this->assertSame('Asia/Tokyo', App::getTimezone());
    }
}
