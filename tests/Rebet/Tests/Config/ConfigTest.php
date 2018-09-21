<?php
namespace Rebet\Tests\Config;

use Rebet\Tests\RebetTestCase;
use Rebet\Config\Config;
use Rebet\Config\Configurable;

class ConfigTest extends RebetTestCase
{
    public function setUp()
    {
        \putenv('PROMISE_TEST=');
        Config::clear();
    }

    public function tearDown()
    {
        \putenv('PROMISE_TEST=');
    }

    public function test_instantiate()
    {
        $this->assertSame('default', Config::instantiate(ConfigTest_MockInstantiate::class, 'mock_instantiate')->value);
        $this->assertSame('arg', Config::instantiate(ConfigTest_MockInstantiate::class, 'mock_instantiate_arg')->value);
    }

    public function test_get()
    {
        $this->assertSame('mysql', Config::get(ConfigTest_Mock::class, 'driver'));
        $this->assertSame('localhost', Config::get(ConfigTest_Mock::class, 'host'));
        $this->assertSame(3306, Config::get(ConfigTest_Mock::class, 'port'));
        $this->assertNull(Config::get(ConfigTest_Mock::class, 'database', false));
        $this->assertSame('default_db', Config::get(ConfigTest_Mock::class, 'database', false, 'default_db'));
        $this->assertSame('refer_database', Config::get(ConfigTest_MockRefer::class, 'database'));
        $this->assertNull(Config::get(ConfigTest_Mock::class, 'user', false));
        $this->assertSame('default_user', Config::get(ConfigTest_Mock::class, 'user', false, 'default_user'));

        Config::framework([
            ConfigTest_Mock::class => [
                'host' => '192.168.1.1',
                'port' => 3307,
                'database' => 'rebet_db',
            ],
            'global' => [
                'lang' => 'en_us'
            ]
        ]);

        $this->assertSame('mysql', Config::get(ConfigTest_Mock::class, 'driver'));
        $this->assertSame('192.168.1.1', Config::get(ConfigTest_Mock::class, 'host'));
        $this->assertSame(3307, Config::get(ConfigTest_Mock::class, 'port'));
        $this->assertSame('rebet_db', Config::get(ConfigTest_Mock::class, 'database'));
        $this->assertSame('rebet_db', Config::get(ConfigTest_Mock::class, 'database', false, 'default_db'));
        $this->assertSame('rebet_db', Config::get(ConfigTest_MockRefer::class, 'database'));
        $this->assertNull(Config::get(ConfigTest_Mock::class, 'user', false));
        $this->assertSame('default_user', Config::get(ConfigTest_Mock::class, 'user', false, 'default_user'));
        $this->assertSame('en_us', Config::get('global', 'lang'));

        Config::application([
            ConfigTest_Mock::class => [
                'port' => 3308,
                'database' => 'rebet_sample',
                'user' => 'rebet_user',
            ],
            'global' => [
                'lang' => 'ja_JP'
            ]
        ]);

        $this->assertSame('mysql', Config::get(ConfigTest_Mock::class, 'driver'));
        $this->assertSame('192.168.1.1', Config::get(ConfigTest_Mock::class, 'host'));
        $this->assertSame(3308, Config::get(ConfigTest_Mock::class, 'port'));
        $this->assertSame('rebet_sample', Config::get(ConfigTest_Mock::class, 'database'));
        $this->assertSame('rebet_sample', Config::get(ConfigTest_Mock::class, 'database', false, 'default_db'));
        $this->assertSame('rebet_sample', Config::get(ConfigTest_MockRefer::class, 'database'));
        $this->assertSame('rebet_user', Config::get(ConfigTest_Mock::class, 'user'));
        $this->assertSame('rebet_user', Config::get(ConfigTest_Mock::class, 'user', false, 'default_user'));
        $this->assertSame('ja_JP', Config::get('global', 'lang'));
        
        Config::runtime([
            ConfigTest_Mock::class => [
                'database' => null,
            ],
            'global' => [
                'lang' => 'en_us'
            ]
        ]);
        $this->assertSame('en_us', Config::get('global', 'lang'));
        $this->assertSame('refer_database', Config::get(ConfigTest_MockRefer::class, 'database'));
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigTest_Mock#database is blank. Please define at application or framework layer.
     */
    public function test_get_blank()
    {
        Config::get(ConfigTest_Mock::class, 'database');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigTest_Mock#undfine is not define. Please check config key name.
     */
    public function test_get_undfine()
    {
        Config::get(ConfigTest_Mock::class, 'undfine');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigTest_Mock#driver is blank. Please define at application layer.
     */
    public function test_get_frameworkOrverrideBlank()
    {
        $this->assertSame('mysql', Config::get(ConfigTest_Mock::class, 'driver'));

        Config::framework([
            ConfigTest_Mock::class => [
                'driver' => null,
            ]
        ]);

        Config::get(ConfigTest_Mock::class, 'driver');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigTest_Mock#driver is blank. Overwritten with blank at application layer.
     */
    public function test_get_applicationOrverrideBlank()
    {
        $this->assertSame('mysql', Config::get(ConfigTest_Mock::class, 'driver'));

        Config::application([
            ConfigTest_Mock::class => [
                'driver' => null,
            ]
        ]);

        Config::get(ConfigTest_Mock::class, 'driver');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException Rebet\Config\ConfigNotDefineException
     * @expectedExceptionMessage Required config Rebet\Tests\Config\ConfigTest_Mock#driver is blank. Overwritten with blank at runtime layer.
     */
    public function test_get_runtimeOrverrideBlank()
    {
        $this->assertSame('mysql', Config::get(ConfigTest_Mock::class, 'driver'));

        Config::runtime([
            ConfigTest_Mock::class => [
                'driver' => null,
            ]
        ]);

        Config::get(ConfigTest_Mock::class, 'driver');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid config key access, the key 'array.1' contains digit only part.
     */
    public function test_get_digitKeyAccessLast()
    {
        Config::application([
            ConfigTest_Mock::class => [
                'array' => [1, 2, 3],
            ]
        ]);
        
        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'array'));
        Config::has(ConfigTest_Mock::class, 'array.1');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid config key access, the key '1' contains digit only part.
     */
    public function test_get_digitKeyAccessOnly()
    {
        Config::has(ConfigTest_Mock::class, '1');
        $this->fail("Never execute.");
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid config key access, the key 'driver.123.dummy' contains digit only part.
     */
    public function test_get_digitKeyAccessMiddle()
    {
        Config::has(ConfigTest_Mock::class, 'driver.123.dummy');
        $this->fail("Never execute.");
    }

    public function test_get_promise()
    {
        ConfigTest_MockPromise::config('dummy', false);

        \putenv('PROMISE_TEST=1');
        $this->assertSame('default', ConfigTest_MockPromise::config('promise_not', false));
        $this->assertSame('1', ConfigTest_MockPromise::config('promise_once', false));
        $this->assertSame('1', ConfigTest_MockPromise::config('promise_every', false));
        $this->assertSame('1', ConfigTest_MockPromiseReferrer::config('refer_promise_once', false));
        $this->assertSame('1', ConfigTest_MockPromiseReferrer::config('refer_promise_every', false));
        
        \putenv('PROMISE_TEST=2');
        $this->assertSame('default', ConfigTest_MockPromise::config('promise_not', false));
        $this->assertSame('1', ConfigTest_MockPromise::config('promise_once', false));
        $this->assertSame('2', ConfigTest_MockPromise::config('promise_every', false));
        $this->assertSame('1', ConfigTest_MockPromiseReferrer::config('refer_promise_once', false));
        $this->assertSame('2', ConfigTest_MockPromiseReferrer::config('refer_promise_every', false));
    }
   
    public function test_get_anonymousClass()
    {
        $a = new class {
            use Configurable;
            public static function defaultConfig()
            {
                return [ 'key' => 'a' ];
            }
        };
        $b = new class {
            use Configurable;
            public static function defaultConfig()
            {
                return [ 'key' => 'b' ];
            }
        };

        $this->assertSame('a', Config::get(get_class($a), 'key'));
        $this->assertSame('b', Config::get(get_class($b), 'key'));
    }

    public function test_has()
    {
        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'driver'));
        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'database'));
        $this->assertFalse(Config::has(ConfigTest_Mock::class, 'undefined'));
        $this->assertFalse(Config::has(ConfigTest_Mock::class, 'invalid'));
        $this->assertFalse(Config::has(ConfigTest_Mock::class, 'nothing'));

        Config::framework([
            ConfigTest_Mock::class => [
                'undefined' => 'defined',
            ]
        ]);

        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'driver'));
        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'database'));
        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'undefined'));
        $this->assertFalse(Config::has(ConfigTest_Mock::class, 'invalid'));
        $this->assertFalse(Config::has(ConfigTest_Mock::class, 'nothing'));

        Config::application([
            ConfigTest_Mock::class => [
                'invalid' => 'not invalid',
            ]
        ]);

        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'driver'));
        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'database'));
        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'undefined'));
        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'invalid'));
        $this->assertFalse(Config::has(ConfigTest_Mock::class, 'nothing'));
        
        Config::runtime([
            ConfigTest_Mock::class => [
                'nothing' => 'something',
            ]
        ]);
        
        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'driver'));
        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'database'));
        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'undefined'));
        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'invalid'));
        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'nothing'));
    }
}


// ========== Mocks ==========

class ConfigTest_Mock
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
}
class ConfigTest_MockRefer
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'database' => Config::refer(ConfigTest_Mock::class, 'database', 'refer_database'),
        ];
    }
}
class ConfigTest_MockPromise
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'promise_not'   => \getenv('PROMISE_TEST') ?: 'default',
            'promise_once'  => Config::promise(function () {
                return \getenv('PROMISE_TEST') ?: 'default';
            }),
            'promise_every' => Config::promise(function () {
                return \getenv('PROMISE_TEST') ?: 'default';
            }, false),
        ];
    }
}
class ConfigTest_MockPromiseReferrer
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'refer_promise_once'  => Config::refer(ConfigTest_MockPromise::class, 'promise_once'),
            'refer_promise_every' => Config::refer(ConfigTest_MockPromise::class, 'promise_every'),
        ];
    }
}

class ConfigTest_MockInstantiate
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'mock_instantiate'     =>  ConfigTest_MockInstantiate::class,
            'mock_instantiate_arg' => [ConfigTest_MockInstantiate::class, 'arg'],
        ];
    }

    public $value = null;
    public function __construct($value = 'default')
    {
        $this->value = $value;
    }
    public static function getInstance()
    {
        return new static('via getInstance()');
    }
    public static function build($value)
    {
        return new static($value.' via build()');
    }
}
