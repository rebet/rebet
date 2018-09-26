<?php
namespace Rebet\Tests\Config;

use Rebet\Tests\RebetTestCase;
use Rebet\Config\Config;
use Rebet\Config\Configurable;

class ConfigurableTest extends RebetTestCase
{
    public function setUp()
    {
        Config::clear();
    }

    public function test_configInstantiate()
    {
        ConfigurableTest_Mock::setDriver(ConfigurableTest_MockChildA::class);
        $this->assertInstanceOf(ConfigurableTest_MockChildA::class, ConfigurableTest_Mock::instantiate('driver'));
    }

    public function test_config()
    {
        $this->assertSame(
            [
                'driver' => 'mysql',
                'host' => 'localhost',
                'port' => 3306,
                'database' => null,
                'user' => null,
            ],
            ConfigurableTest_Mock::config()
        );
        $this->assertSame('mysql', ConfigurableTest_Mock::config('driver'));
        $this->assertNull(ConfigurableTest_Mock::config('database', false));
        $this->assertSame('default_db', ConfigurableTest_Mock::config('database', false, 'default_db'));
        $this->assertSame('mysql', ConfigurableTest_Mock::configInStatic('driver'));

        $mock = new ConfigurableTest_Mock();
        $this->assertSame('mysql', $mock->configInMember('driver'));

        Config::application([
            ConfigurableTest_Mock::class => [
                'driver' => 'pgsql'
            ]
        ]);

        $this->assertSame(
            [
                'driver' => 'pgsql',
                'host' => 'localhost',
                'port' => 3306,
                'database' => null,
                'user' => null,
            ],
            ConfigurableTest_Mock::config()
        );
        $this->assertSame('pgsql', ConfigurableTest_Mock::config('driver'));
        $this->assertSame('pgsql', ConfigurableTest_Mock::configInStatic('driver'));
        $this->assertSame('pgsql', $mock->configInMember('driver'));

        ConfigurableTest_Mock::setDriver('new driver');

        $this->assertSame('new driver', ConfigurableTest_Mock::config('driver'));
        $this->assertSame('new driver', ConfigurableTest_Mock::configInStatic('driver'));
        $this->assertSame('new driver', $mock->configInMember('driver'));
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigurableTest_Mock#database is blank or not define.
     */
    public function test_config_blank()
    {
        ConfigurableTest_Mock::config('database');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigurableTest_Mock#database is blank or not define.
     */
    public function test_config_blankInStatic()
    {
        ConfigurableTest_Mock::configInStatic('database');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigurableTest_Mock#database is blank or not define.
     */
    public function test_config_blankInMember()
    {
        $mock = new ConfigurableTest_Mock();
        $mock->configInMember('database');
        $this->fail("Never execute.");
    }

    public function test_config_extends()
    {
        $mock   = new ConfigurableTest_Mock();
        $childA = new ConfigurableTest_MockChildA();
        $childB = new ConfigurableTest_MockChildB();
        $childC = new ConfigurableTest_MockChildC();
        
        $this->assertSame(
            [
                'driver' => 'sqlite',
                'host' => 'localhost',
                'port' => 3306,
                'database' => null,
                'user' => null,
                'encode' => 'utf8',
                'new_key' => 'new_value',
            ],
            ConfigurableTest_MockGrandChildA::defaultConfig()
        );

        $this->assertSame('mysql', ConfigurableTest_Mock::config('driver'));
        $this->assertSame('mysql', ConfigurableTest_Mock::configInStatic('driver'));
        $this->assertSame('mysql', $mock->configInMember('driver'));
        $this->assertNull(ConfigurableTest_Mock::config('encode', false));

        $this->assertSame('mysql', ConfigurableTest_MockChildA::config('driver'));
        $this->assertSame('mysql', ConfigurableTest_MockChildA::configInStatic('driver'));
        $this->assertSame('mysql', $childA->configInMember('driver'));
        $this->assertNull(ConfigurableTest_MockChildA::config('encode', false));

        Config::application([
            ConfigurableTest_MockChildA::class => [
                'driver' => 'oracle',
                'encode' => 'utf8'
            ]
        ]);

        $this->assertSame('oracle', ConfigurableTest_MockChildA::config('driver'));
        $this->assertSame('oracle', ConfigurableTest_MockChildA::configInStatic('driver'));
        $this->assertSame('oracle', $childA->configInMember('driver'));
        $this->assertSame('utf8', ConfigurableTest_MockChildA::config('encode'));

        $this->assertSame('sqlite', ConfigurableTest_MockChildB::config('driver'));
        $this->assertSame('sqlite', ConfigurableTest_MockChildB::configInStatic('driver'));
        $this->assertSame('sqlite', $childB->configInMember('driver'));
        $this->assertSame('utf8mb4', ConfigurableTest_MockChildB::config('encode'));
        $this->assertSame('utf8mb4', ConfigurableTest_MockChildB::configInStatic('encode'));
        $this->assertSame('utf8mb4', $childB->configInMember('encode'));

        $this->assertSame('pgsql', ConfigurableTest_MockChildC::config('driver'));
        $this->assertSame('pgsql', ConfigurableTest_MockChildC::configInStatic('driver'));
        $this->assertSame('pgsql', $childC->configInMember('driver'));
        $this->assertNull(ConfigurableTest_MockChildC::config('host', false));

        $this->assertSame('mysql', ConfigurableTest_Mock::config('driver'));
        $this->assertSame('mysql', ConfigurableTest_Mock::configInStatic('driver'));
        $this->assertSame('mysql', $mock->configInMember('driver'));
        $this->assertNull(ConfigurableTest_Mock::config('encode', false));
    }
}


// ========== Mocks ==========

class ConfigurableTest_Mock
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => null,
            'user' => null,
        ];
    }

    public static function configInStatic($key)
    {
        return self::config($key);
    }

    public function configInMember($key)
    {
        return self::config($key);
    }

    public static function setDriver(string $driver)
    {
        static::setConfig(['driver' => $driver]);
    }

    public static function instantiate(string $key)
    {
        return static::configInstantiate($key);
    }
}
class ConfigurableTest_MockChildA extends ConfigurableTest_Mock
{
    // No override
}
class ConfigurableTest_MockChildB extends ConfigurableTest_Mock
{
    public static function defaultConfig()
    {
        return self::overrideConfig([
            'driver' => 'sqlite',
            'encode' => 'utf8mb4',
        ]);
    }
}
class ConfigurableTest_MockChildC extends ConfigurableTest_Mock
{
    public static function defaultConfig()
    {
        return [
            'driver' => 'pgsql',
        ];
    }
}
class ConfigurableTest_MockGrandChildA extends ConfigurableTest_MockChildB
{
    public static function defaultConfig()
    {
        return self::overrideConfig([
            'encode' => 'utf8',
            'new_key' => 'new_value',
        ]);
    }
}
