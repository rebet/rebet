<?php
namespace Rebet\Tests\Config;

use Rebet\Tests\RebetTestCase;
use Rebet\Config\Config;
use Rebet\Config\Configable;

/*
 * モック定義
 */
class ConfigableTest_Mock {
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

    public static function configInStatic($key) {
        return self::config($key);
    }

    public function configInMember($key) {
        return self::config($key);
    }

    public static function setDriver(string $driver) {
        static::setConfig(['driver' => $driver]);
    }
}
class ConfigableTest_MockChildA extends ConfigableTest_Mock {
    // No override
}
class ConfigableTest_MockChildB extends ConfigableTest_Mock {
    public static function defaultConfig() {
        return \array_merge(parent::defaultConfig(),[
            'driver' => 'sqlite',
            'encode' => 'utf8mb4',
        ]);
    }
}
class ConfigableTest_MockChildC extends ConfigableTest_Mock {
    public static function defaultConfig() {
        return [
            'driver' => 'pgsql',
        ];
    }
}

/*
 * テストコード
 */
class ConfigableTest extends RebetTestCase {
    public function setUp() {
        Config::clear();
    }

    public function test_config() {
        $this->assertSame('mysql', ConfigableTest_Mock::config('driver'));
        $this->assertNull(ConfigableTest_Mock::config('database', false));
        $this->assertSame('default_db', ConfigableTest_Mock::config('database', false, 'default_db'));
        $this->assertSame('mysql', ConfigableTest_Mock::configInStatic('driver'));

        $mock = new ConfigableTest_Mock();
        $this->assertSame('mysql', $mock->configInMember('driver'));

        Config::application([
            ConfigableTest_Mock::class => [
                'driver' => 'pgsql'
            ]
        ]);

        $this->assertSame('pgsql', ConfigableTest_Mock::config('driver'));
        $this->assertSame('pgsql', ConfigableTest_Mock::configInStatic('driver'));
        $this->assertSame('pgsql', $mock->configInMember('driver'));

        ConfigableTest_Mock::setDriver('new driver');

        $this->assertSame('new driver', ConfigableTest_Mock::config('driver'));
        $this->assertSame('new driver', ConfigableTest_Mock::configInStatic('driver'));
        $this->assertSame('new driver', $mock->configInMember('driver'));
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigableTest_Mock#database is blank. Please define at application or framework layer.
     */
    public function test_config_blank() {
        ConfigableTest_Mock::config('database');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigableTest_Mock#database is blank. Please define at application or framework layer.
     */
    public function test_config_blankInStatic() {
        ConfigableTest_Mock::configInStatic('database');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigableTest_Mock#database is blank. Please define at application or framework layer.
     */
    public function test_config_blankInMember() {
        $mock = new ConfigableTest_Mock();
        $mock->configInMember('database');
        $this->fail("Never execute.");
    }

    public function test_config_extends() {
        $mock   = new ConfigableTest_Mock();
        $childA = new ConfigableTest_MockChildA();
        $childB = new ConfigableTest_MockChildB();
        $childC = new ConfigableTest_MockChildC();

        $this->assertSame('mysql', ConfigableTest_Mock::config('driver'));
        $this->assertSame('mysql', ConfigableTest_Mock::configInStatic('driver'));
        $this->assertSame('mysql', $mock->configInMember('driver'));
        $this->assertNull(ConfigableTest_Mock::config('encode', false));

        $this->assertSame('mysql', ConfigableTest_MockChildA::config('driver'));
        $this->assertSame('mysql', ConfigableTest_MockChildA::configInStatic('driver'));
        $this->assertSame('mysql', $childA->configInMember('driver'));
        $this->assertNull(ConfigableTest_MockChildA::config('encode', false));

        Config::application([
            ConfigableTest_MockChildA::class => [
                'driver' => 'oracle',
                'encode' => 'utf8'
            ]
        ]);

        $this->assertSame('oracle', ConfigableTest_MockChildA::config('driver'));
        $this->assertSame('oracle', ConfigableTest_MockChildA::configInStatic('driver'));
        $this->assertSame('oracle', $childA->configInMember('driver'));
        $this->assertSame('utf8', ConfigableTest_MockChildA::config('encode'));

        $this->assertSame('sqlite', ConfigableTest_MockChildB::config('driver'));
        $this->assertSame('sqlite', ConfigableTest_MockChildB::configInStatic('driver'));
        $this->assertSame('sqlite', $childB->configInMember('driver'));
        $this->assertSame('utf8mb4', ConfigableTest_MockChildB::config('encode'));
        $this->assertSame('utf8mb4', ConfigableTest_MockChildB::configInStatic('encode'));
        $this->assertSame('utf8mb4', $childB->configInMember('encode'));

        $this->assertSame('pgsql', ConfigableTest_MockChildC::config('driver'));
        $this->assertSame('pgsql', ConfigableTest_MockChildC::configInStatic('driver'));
        $this->assertSame('pgsql', $childC->configInMember('driver'));
        $this->assertNull(ConfigableTest_MockChildC::config('host', false));

        $this->assertSame('mysql', ConfigableTest_Mock::config('driver'));
        $this->assertSame('mysql', ConfigableTest_Mock::configInStatic('driver'));
        $this->assertSame('mysql', $mock->configInMember('driver'));
        $this->assertNull(ConfigableTest_Mock::config('encode', false));
    }
}
