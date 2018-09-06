<?php
namespace Rebet\Tests\Config;

use Rebet\Tests\RebetTestCase;
use Rebet\Config\Config;
use Rebet\Config\Configable;

/*
 * モック定義
 */
class ConfigableTestMock {
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
}
class ConfigableTestMockChildA extends ConfigableTestMock {
    // No override
}
class ConfigableTestMockChildB extends ConfigableTestMock {
    public static function defaultConfig() {
        return \array_merge(parent::defaultConfig(),[
            'driver' => 'sqlite',
            'encode' => 'utf8mb4',
        ]);
    }
}
class ConfigableTestMockChildC extends ConfigableTestMock {
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
        $this->assertSame('mysql', ConfigableTestMock::config('driver'));
        $this->assertNull(ConfigableTestMock::config('database', false));
        $this->assertSame('default_db', ConfigableTestMock::config('database', false, 'default_db'));
        $this->assertSame('mysql', ConfigableTestMock::configInStatic('driver'));

        $mock = new ConfigableTestMock();
        $this->assertSame('mysql', $mock->configInMember('driver'));

        Config::application([
            ConfigableTestMock::class => [
                'driver' => 'pgsql'
            ]
        ]);

        $this->assertSame('pgsql', ConfigableTestMock::config('driver'));
        $this->assertSame('pgsql', ConfigableTestMock::configInStatic('driver'));
        $this->assertSame('pgsql', $mock->configInMember('driver'));
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigableTestMock#database is blank. Please define at application or framework layer.
     */
    public function test_config_blank() {
        ConfigableTestMock::config('database');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigableTestMock#database is blank. Please define at application or framework layer.
     */
    public function test_config_blankInStatic() {
        ConfigableTestMock::configInStatic('database');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigableTestMock#database is blank. Please define at application or framework layer.
     */
    public function test_config_blankInMember() {
        $mock = new ConfigableTestMock();
        $mock->configInMember('database');
        $this->fail("Never execute.");
    }

    public function test_config_extends() {
        $mock   = new ConfigableTestMock();
        $childA = new ConfigableTestMockChildA();
        $childB = new ConfigableTestMockChildB();
        $childC = new ConfigableTestMockChildC();

        $this->assertSame('mysql', ConfigableTestMock::config('driver'));
        $this->assertSame('mysql', ConfigableTestMock::configInStatic('driver'));
        $this->assertSame('mysql', $mock->configInMember('driver'));
        $this->assertNull(ConfigableTestMock::config('encode', false));

        $this->assertSame('mysql', ConfigableTestMockChildA::config('driver'));
        $this->assertSame('mysql', ConfigableTestMockChildA::configInStatic('driver'));
        $this->assertSame('mysql', $childA->configInMember('driver'));
        $this->assertNull(ConfigableTestMockChildA::config('encode', false));

        Config::application([
            ConfigableTestMockChildA::class => [
                'driver' => 'oracle',
                'encode' => 'utf8'
            ]
        ]);

        $this->assertSame('oracle', ConfigableTestMockChildA::config('driver'));
        $this->assertSame('oracle', ConfigableTestMockChildA::configInStatic('driver'));
        $this->assertSame('oracle', $childA->configInMember('driver'));
        $this->assertSame('utf8', ConfigableTestMockChildA::config('encode'));

        $this->assertSame('sqlite', ConfigableTestMockChildB::config('driver'));
        $this->assertSame('sqlite', ConfigableTestMockChildB::configInStatic('driver'));
        $this->assertSame('sqlite', $childB->configInMember('driver'));
        $this->assertSame('utf8mb4', ConfigableTestMockChildB::config('encode'));
        $this->assertSame('utf8mb4', ConfigableTestMockChildB::configInStatic('encode'));
        $this->assertSame('utf8mb4', $childB->configInMember('encode'));

        $this->assertSame('pgsql', ConfigableTestMockChildC::config('driver'));
        $this->assertSame('pgsql', ConfigableTestMockChildC::configInStatic('driver'));
        $this->assertSame('pgsql', $childC->configInMember('driver'));
        $this->assertNull(ConfigableTestMockChildC::config('host', false));

        $this->assertSame('mysql', ConfigableTestMock::config('driver'));
        $this->assertSame('mysql', ConfigableTestMock::configInStatic('driver'));
        $this->assertSame('mysql', $mock->configInMember('driver'));
        $this->assertNull(ConfigableTestMock::config('encode', false));
    }
}
