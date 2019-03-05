<?php
namespace Rebet\Tests\Config;

use Rebet\Config\Config;
use Rebet\Config\Configurable;
use Rebet\Config\Layer;
use Rebet\Tests\RebetTestCase;

class ConfigurableTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_configInstantiate()
    {
        ConfigurableTest_Mock::setDriver(ConfigurableTest_MockChildInherit::class);
        $this->assertInstanceOf(ConfigurableTest_MockChildInherit::class, ConfigurableTest_Mock::instantiate('driver'));
    }

    public function test_config()
    {
        $this->assertSame(
            [
                'driver'   => 'mysql',
                'host'     => 'localhost',
                'port'     => 3306,
                'database' => null,
                'user'     => null,
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
                'driver'   => 'pgsql',
                'host'     => 'localhost',
                'port'     => 3306,
                'database' => null,
                'user'     => null,
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
     * @expectedException Rebet\Config\Exception\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigurableTest_Mock#database is blank or not define.
     */
    public function test_config_blank()
    {
        ConfigurableTest_Mock::config('database');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\Exception\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigurableTest_Mock#database is blank or not define.
     */
    public function test_config_blankInStatic()
    {
        ConfigurableTest_Mock::configInStatic('database');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\Exception\ConfigNotDefineException
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
        $mock     = new ConfigurableTest_Mock();
        $inherit  = new ConfigurableTest_MockChildInherit();
        $share    = new ConfigurableTest_MockChildShare();
        $override = new ConfigurableTest_MockChildOverride();
        $hide     = new ConfigurableTest_MockChildHide();

        $this->assertSame(
            [
                'driver'   => 'mysql',
                'host'     => 'localhost',
                'port'     => 3306,
                'database' => null,
                'user'     => null,
            ],
            ConfigurableTest_Mock::config()
        );

        $this->assertSame(
            [
                'driver'   => 'mysql',
                'host'     => 'localhost',
                'port'     => 3306,
                'database' => null,
                'user'     => null,
            ],
            ConfigurableTest_MockChildInherit::config()
        );

        $this->assertSame(
            [
                'driver'   => 'mysql',
                'host'     => 'localhost',
                'port'     => 3306,
                'database' => null,
                'user'     => null,
            ],
            ConfigurableTest_MockChildShare::config()
        );

        $this->assertSame(
            [
                'driver'   => 'sqlite',
                'host'     => 'localhost',
                'port'     => 3306,
                'database' => null,
                'user'     => null,
                'encode'   => 'utf8mb4',
            ],
            ConfigurableTest_MockChildOverride::config()
        );

        $this->assertSame(
            [
                'driver' => 'pgsql',
            ],
            ConfigurableTest_MockChildHide::config()
        );

        $this->assertSame(
            [
                'driver'   => 'sqlite',
                'host'     => 'localhost',
                'port'     => 3306,
                'database' => null,
                'user'     => null,
                'encode'   => 'utf8',
                'new_key'  => 'new_value',
            ],
            ConfigurableTest_MockGrandChildOverride::config()
        );

        $this->assertSame('mysql', ConfigurableTest_Mock::config('driver'));
        $this->assertSame('mysql', ConfigurableTest_Mock::configInStatic('driver'));
        $this->assertSame('mysql', $mock->configInMember('driver'));
        $this->assertNull(ConfigurableTest_Mock::config('encode', false));

        $this->assertSame('mysql', ConfigurableTest_MockChildInherit::config('driver'));
        $this->assertSame('mysql', ConfigurableTest_MockChildInherit::configInStatic('driver'));
        $this->assertSame('mysql', $inherit->configInMember('driver'));
        $this->assertNull(ConfigurableTest_MockChildInherit::config('encode', false));

        $this->assertSame('mysql', ConfigurableTest_MockChildShare::config('driver'));
        $this->assertSame('mysql', ConfigurableTest_MockChildShare::configInStatic('driver'));
        $this->assertSame('mysql', $share->configInMember('driver'));
        $this->assertNull(ConfigurableTest_MockChildShare::config('encode', false));

        $this->assertSame('sqlite', ConfigurableTest_MockChildOverride::config('driver'));
        $this->assertSame('sqlite', ConfigurableTest_MockChildOverride::configInStatic('driver'));
        $this->assertSame('sqlite', $override->configInMember('driver'));
        $this->assertSame('utf8mb4', ConfigurableTest_MockChildOverride::config('encode'));

        $this->assertSame('pgsql', ConfigurableTest_MockChildHide::config('driver'));
        $this->assertSame('pgsql', ConfigurableTest_MockChildHide::configInStatic('driver'));
        $this->assertSame('pgsql', $hide->configInMember('driver'));
        $this->assertNull(ConfigurableTest_MockChildShare::config('encode', false));
        $this->assertNull(ConfigurableTest_MockChildHide::config('host', false));

        Config::application([
            ConfigurableTest_Mock::class => [
                'driver' => 'oracle',
            ]
        ]);

        $this->assertSame('oracle', ConfigurableTest_Mock::config('driver'));
        $this->assertSame('mysql', ConfigurableTest_MockChildInherit::config('driver'));
        $this->assertSame('oracle', ConfigurableTest_MockChildShare::config('driver'));
        $this->assertSame('sqlite', ConfigurableTest_MockChildOverride::config('driver'));
        $this->assertSame('pgsql', ConfigurableTest_MockChildHide::config('driver'));

        Config::application([
            ConfigurableTest_MockChildShare::class => [
                'driver' => 'mariadb',
            ]
        ]);

        $this->assertSame('oracle', ConfigurableTest_Mock::config('driver'));
        $this->assertSame('mysql', ConfigurableTest_MockChildInherit::config('driver'));
        $this->assertSame('mariadb', ConfigurableTest_MockChildShare::config('driver'));
        $this->assertSame('sqlite', ConfigurableTest_MockChildOverride::config('driver'));
        $this->assertSame('pgsql', ConfigurableTest_MockChildHide::config('driver'));

        $this->assertSame('localhost', ConfigurableTest_Mock::config('host'));
        $this->assertSame('localhost', ConfigurableTest_MockChildShare::config('host'));

        Config::application([
            ConfigurableTest_Mock::class => [
                'host' => '192.168.1.1',
            ]
        ]);

        $this->assertSame('192.168.1.1', ConfigurableTest_Mock::config('host'));
        $this->assertSame('192.168.1.1', ConfigurableTest_MockChildShare::config('host'));
        $this->assertSame('oracle', ConfigurableTest_Mock::config('driver'));
        $this->assertSame('mariadb', ConfigurableTest_MockChildShare::config('driver'));
    }

    public function test_config_clear()
    {
        $this->assertSame('mysql', ConfigurableTest_Mock::config('driver'));

        Config::framework([
            ConfigurableTest_Mock::class => [
                'driver' => 'oracle',
            ]
        ]);
        $this->assertSame('oracle', ConfigurableTest_Mock::config('driver'));

        Config::application([
            ConfigurableTest_Mock::class => [
                'driver' => 'postgresql',
            ]
        ]);
        $this->assertSame('postgresql', ConfigurableTest_Mock::config('driver'));

        ConfigurableTest_Mock::clear(Layer::APPLICATION);
        $this->assertSame('oracle', ConfigurableTest_Mock::config('driver'));

        ConfigurableTest_Mock::clear();
        $this->assertSame('mysql', ConfigurableTest_Mock::config('driver'));
    }
}


// ========== Mocks ==========

class ConfigurableTest_Mock
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'driver'   => 'mysql',
            'host'     => 'localhost',
            'port'     => 3306,
            'database' => null,
            'user'     => null,
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

    public static function clear(string ...$layers)
    {
        return static::clearConfig(...$layers);
    }
}
class ConfigurableTest_MockChildInherit extends ConfigurableTest_Mock
{
    // No override
}
class ConfigurableTest_MockChildOverride extends ConfigurableTest_Mock
{
    public static function defaultConfig()
    {
        return self::copyConfigFrom(parent::class, [
            'driver' => 'sqlite',
            'encode' => 'utf8mb4',
        ]);
    }
}
class ConfigurableTest_MockChildShare extends ConfigurableTest_Mock
{
    public static function defaultConfig()
    {
        return Config::refer(parent::class);
    }
}
class ConfigurableTest_MockChildHide extends ConfigurableTest_Mock
{
    public static function defaultConfig()
    {
        return [
            'driver' => 'pgsql',
        ];
    }
}
class ConfigurableTest_MockGrandChildOverride extends ConfigurableTest_MockChildOverride
{
    public static function defaultConfig()
    {
        return self::copyConfigFrom(parent::class, [
            'encode'  => 'utf8',
            'new_key' => 'new_value',
        ]);
    }
}
