<?php
namespace Rebet\Tests\Config;

use Rebet\Tests\RebetTestCase;
use Rebet\Config\Config;
use Rebet\Config\Configable;

class ConfigTestMock {
    use Configable;
    public static function defaultConfig() {
        return [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => null,
            'user' => null,
        ];
    }
}

class ConfigTest extends RebetTestCase {
    public function setUp() {
        Config::clear();
    }

    public function test_get() {
        $this->assertSame('mysql', Config::get(ConfigTestMock::class, 'driver'));
        $this->assertSame('localhost', Config::get(ConfigTestMock::class, 'host'));
        $this->assertSame(3306, Config::get(ConfigTestMock::class, 'port'));
        $this->assertNull(Config::get(ConfigTestMock::class, 'database', false));
        $this->assertSame('default_db', Config::get(ConfigTestMock::class, 'database', false, 'default_db'));
        $this->assertNull(Config::get(ConfigTestMock::class, 'user', false));
        $this->assertSame('default_user', Config::get(ConfigTestMock::class, 'user', false, 'default_user'));

        Config::framework([
            ConfigTestMock::class => [
                'host' => '192.168.1.1',
                'port' => 3307,
                'database' => 'rebet_db',
            ],
            'global' => [
                'lang' => 'en_us'
            ]
        ]);

        $this->assertSame('mysql', Config::get(ConfigTestMock::class, 'driver'));
        $this->assertSame('192.168.1.1', Config::get(ConfigTestMock::class, 'host'));
        $this->assertSame(3307, Config::get(ConfigTestMock::class, 'port'));
        $this->assertSame('rebet_db', Config::get(ConfigTestMock::class, 'database'));
        $this->assertSame('rebet_db', Config::get(ConfigTestMock::class, 'database', false, 'default_db'));
        $this->assertNull(Config::get(ConfigTestMock::class, 'user', false));
        $this->assertSame('default_user', Config::get(ConfigTestMock::class, 'user', false, 'default_user'));
        $this->assertSame('en_us', Config::get('global', 'lang'));

        Config::application([
            ConfigTestMock::class => [
                'port' => 3308,
                'database' => 'rebet_sample',
                'user' => 'rebet_user',
            ],
            'global' => [
                'lang' => 'ja_JP'
            ]
        ]);

        $this->assertSame('mysql', Config::get(ConfigTestMock::class, 'driver'));
        $this->assertSame('192.168.1.1', Config::get(ConfigTestMock::class, 'host'));
        $this->assertSame(3308, Config::get(ConfigTestMock::class, 'port'));
        $this->assertSame('rebet_sample', Config::get(ConfigTestMock::class, 'database'));
        $this->assertSame('rebet_sample', Config::get(ConfigTestMock::class, 'database', false, 'default_db'));
        $this->assertSame('rebet_user', Config::get(ConfigTestMock::class, 'user'));
        $this->assertSame('rebet_user', Config::get(ConfigTestMock::class, 'user', false, 'default_user'));
        $this->assertSame('ja_JP', Config::get('global', 'lang'));
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigTestMock#database is blank. Please define at application or framework layer.
     */
    public function test_get_blank() {
        Config::get(ConfigTestMock::class, 'database');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigTestMock#undfine is not define. Please check config key name.
     */
    public function test_get_undfine() {
        Config::get(ConfigTestMock::class, 'undfine');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigTestMock#driver is blank. Please define at application layer.
     */
    public function test_get_frameworkOrverrideBlank() {
        $this->assertSame('mysql', Config::get(ConfigTestMock::class, 'driver'));

        Config::framework([
            ConfigTestMock::class => [
                'driver' => null,
            ]
        ]);

        Config::get(ConfigTestMock::class, 'driver');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigTestMock#driver is blank. Overwritten with blank at application layer.
     */
    public function test_get_applicationOrverrideBlank() {
        $this->assertSame('mysql', Config::get(ConfigTestMock::class, 'driver'));

        Config::application([
            ConfigTestMock::class => [
                'driver' => null,
            ]
        ]);

        Config::get(ConfigTestMock::class, 'driver');
        $this->fail("Never execute.");
    }

    public function test_get_anonymousClass() {
        $a = new class{
            use Configable;
            public static function defaultConfig() { return [ 'key' => 'a' ]; }
        };
        $b = new class{
            use Configable;
            public static function defaultConfig() { return [ 'key' => 'b' ]; }
        };

        $this->assertSame('a', Config::get(get_class($a), 'key'));
        $this->assertSame('b', Config::get(get_class($b), 'key'));
    }

    public function test_has() {
        $this->assertTrue(Config::has(ConfigTestMock::class, 'driver'));
        $this->assertTrue(Config::has(ConfigTestMock::class, 'database'));
        $this->assertFalse(Config::has(ConfigTestMock::class, 'undefined'));
        $this->assertFalse(Config::has(ConfigTestMock::class, 'invalid'));

        Config::framework([
            ConfigTestMock::class => [
                'undefined' => 'defined',
            ]
        ]);

        $this->assertTrue(Config::has(ConfigTestMock::class, 'driver'));
        $this->assertTrue(Config::has(ConfigTestMock::class, 'database'));
        $this->assertTrue(Config::has(ConfigTestMock::class, 'undefined'));
        $this->assertFalse(Config::has(ConfigTestMock::class, 'invalid'));

        Config::application([
            ConfigTestMock::class => [
                'invalid' => 'not invalid',
            ]
        ]);

        $this->assertTrue(Config::has(ConfigTestMock::class, 'driver'));
        $this->assertTrue(Config::has(ConfigTestMock::class, 'database'));
        $this->assertTrue(Config::has(ConfigTestMock::class, 'undefined'));
        $this->assertTrue(Config::has(ConfigTestMock::class, 'invalid'));
    }
}
