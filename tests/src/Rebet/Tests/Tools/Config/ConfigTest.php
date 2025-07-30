<?php
namespace Rebet\Tests\Tools\Config;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\Config\Exception\ConfigNotDefineException;
use Rebet\Tools\Config\Layer;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Exception\LogicException;

class ConfigTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        \putenv('PROMISE_TEST=');
    }

    protected function tearDown() : void
    {
        \putenv('PROMISE_TEST=');
    }

    public function test_instantiate()
    {
        $this->assertSame('default', Config::instantiate(ConfigTest_MockInstantiate::class, 'mock_instantiate')->value);
        $this->assertSame('arg', Config::instantiate(ConfigTest_MockInstantiate::class, 'mock_instantiate_arg')->value);
        $this->assertSame('map', Config::instantiate(ConfigTest_MockInstantiate::class, 'mock_instantiate_map')->value);

        $this->assertSame('runtime_arg', Config::instantiate(ConfigTest_MockInstantiate::class, 'mock_instantiate', ['runtime_arg'])->value);
        $this->assertSame('arg', Config::instantiate(ConfigTest_MockInstantiate::class, 'mock_instantiate_arg', ['runtime_arg'])->value);                    // append 2nd args, so do not change parameter
        $this->assertSame('runtime_arg', Config::instantiate(ConfigTest_MockInstantiate::class, 'mock_instantiate_map', ['value' => 'runtime_arg'])->value);
    }

    public function test_clear()
    {
        $this->assertSame('mysql', Config::get(ConfigTest_Mock::class, 'driver'));
        $this->assertSame('a', Config::get(ConfigTest_MockOption::class, 'map.a'));
        $this->assertSame('b', Config::get(ConfigTest_MockOption::class, 'map.b'));

        Config::framework([
            ConfigTest_Mock::class => [
                'driver' => 'sqlite',
            ],
            ConfigTest_MockOption::class => [
                'map' => ['a' => 'A', 'b' => 'B']
            ]
        ]);
        $this->assertSame('sqlite', Config::get(ConfigTest_Mock::class, 'driver'));
        $this->assertSame('A', Config::get(ConfigTest_MockOption::class, 'map.a'));
        $this->assertSame('B', Config::get(ConfigTest_MockOption::class, 'map.b'));

        Config::application([
            ConfigTest_MockOption::class => [
                'map' => ['b' => 'BB']
            ]
        ]);
        $this->assertSame('sqlite', Config::get(ConfigTest_Mock::class, 'driver'));
        $this->assertSame('A', Config::get(ConfigTest_MockOption::class, 'map.a'));
        $this->assertSame('BB', Config::get(ConfigTest_MockOption::class, 'map.b'));

        Config::clear(ConfigTest_MockOption::class, Layer::FRAMEWORK);
        $this->assertSame('sqlite', Config::get(ConfigTest_Mock::class, 'driver'));
        $this->assertSame('a', Config::get(ConfigTest_MockOption::class, 'map.a'));
        $this->assertSame('BB', Config::get(ConfigTest_MockOption::class, 'map.b'));

        Config::clear(ConfigTest_MockOption::class);
        $this->assertSame('sqlite', Config::get(ConfigTest_Mock::class, 'driver'));
        $this->assertSame('a', Config::get(ConfigTest_MockOption::class, 'map.a'));
        $this->assertSame('b', Config::get(ConfigTest_MockOption::class, 'map.b'));

        Config::clear();
        $this->assertSame('mysql', Config::get(ConfigTest_Mock::class, 'driver'));
        $this->assertSame('a', Config::get(ConfigTest_MockOption::class, 'map.a'));
        $this->assertSame('b', Config::get(ConfigTest_MockOption::class, 'map.b'));
    }

    public function test_get()
    {
        $this->assertSame(
            [
                'driver'   => 'mysql',
                'host'     => 'localhost',
                'port'     => 3306,
                'database' => null,
                'user'     => null,
            ],
            Config::get(ConfigTest_Mock::class)
        );
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
                'host'     => '192.168.1.1',
                'port'     => 3307,
                'database' => 'rebet_db',
            ],
            'global' => [
                'lang' => 'en_us'
            ]
        ]);

        $this->assertSame(
            [
                'driver'   => 'mysql',
                'host'     => '192.168.1.1',
                'port'     => 3307,
                'database' => 'rebet_db',
                'user'     => null,
            ],
            Config::get(ConfigTest_Mock::class)
        );
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
                'port'     => 3308,
                'database' => 'rebet_sample',
                'user'     => 'rebet_user',
            ],
            'global' => [
                'lang' => 'ja_JP'
            ]
        ]);

        $this->assertSame(
            [
                'driver'   => 'mysql',
                'host'     => '192.168.1.1',
                'port'     => 3308,
                'database' => 'rebet_sample',
                'user'     => 'rebet_user',
            ],
            Config::get(ConfigTest_Mock::class)
        );
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
        $this->assertSame(
            [
                'driver'   => 'mysql',
                'host'     => '192.168.1.1',
                'port'     => 3308,
                'database' => null,
                'user'     => 'rebet_user',
            ],
            Config::get(ConfigTest_Mock::class)
        );
        $this->assertSame('en_us', Config::get('global', 'lang'));
        $this->assertSame('refer_database', Config::get(ConfigTest_MockRefer::class, 'database'));
    }

    public function test_get_blank()
    {
        $this->expectException(ConfigNotDefineException::class);
        $this->expectExceptionMessage("Required config Rebet\Tests\Tools\Config\ConfigTest_Mock.database is blank or not define.");

        Config::get(ConfigTest_Mock::class, 'database');
    }

    public function test_get_undfine()
    {
        $this->expectException(ConfigNotDefineException::class);
        $this->expectExceptionMessage("Required config Rebet\Tests\Tools\Config\ConfigTest_Mock.undfine is blank or not define.");

        Config::get(ConfigTest_Mock::class, 'undfine');
    }

    public function test_get_frameworkOrverrideBlank()
    {
        $this->expectException(ConfigNotDefineException::class);
        $this->expectExceptionMessage("Required config Rebet\Tests\Tools\Config\ConfigTest_Mock.driver is blank or not define.");

        $this->assertSame('mysql', Config::get(ConfigTest_Mock::class, 'driver'));

        Config::framework([
            ConfigTest_Mock::class => [
                'driver' => null,
            ]
        ]);

        Config::get(ConfigTest_Mock::class, 'driver');
    }

    public function test_get_applicationOrverrideBlank()
    {
        $this->expectException(ConfigNotDefineException::class);
        $this->expectExceptionMessage("Required config Rebet\Tests\Tools\Config\ConfigTest_Mock.driver is blank or not define.");

        $this->assertSame('mysql', Config::get(ConfigTest_Mock::class, 'driver'));

        Config::application([
            ConfigTest_Mock::class => [
                'driver' => null,
            ]
        ]);

        Config::get(ConfigTest_Mock::class, 'driver');
    }

    public function test_get_runtimeOrverrideBlank()
    {
        $this->expectException(ConfigNotDefineException::class);
        $this->expectExceptionMessage("Required config Rebet\Tests\Tools\Config\ConfigTest_Mock.driver is blank or not define.");

        $this->assertSame('mysql', Config::get(ConfigTest_Mock::class, 'driver'));

        Config::runtime([
            ConfigTest_Mock::class => [
                'driver' => null,
            ]
        ]);

        Config::get(ConfigTest_Mock::class, 'driver');
    }

    public function test_get_optionNothing()
    {
        $this->assertSame(
            [
                'map'    => ['a' => 'a', 'b' => 'b'],
                'array'  => ['a', 'b'],
                'parent' => [
                    'map'   => ['a' => 'a', 'b' => 'b'],
                    'array' => ['a', 'b'],
                ],
            ],
            Config::get(ConfigTest_MockOption::class)
        );

        Config::framework([
            ConfigTest_MockOption::class => [
                'map'    => ['a' => 'A', 'c' => 'C'],
                'array'  => ['c'],
                'parent' => [
                    'map'    => ['a' => 'aa', 'c' => 'cc'],
                    'array'  => ['cc'],
                ],
            ]
        ]);

        $this->assertSame(
            [
                'map'    => ['a' => 'A', 'b' => 'b', 'c' => 'C'],
                'array'  => ['c', 'a', 'b'],
                'parent' => [
                    'map'    => ['a' => 'aa', 'b' => 'b', 'c' => 'cc'],
                    'array'  => ['cc', 'a', 'b'],
                ],
            ],
            Config::get(ConfigTest_MockOption::class)
        );
        $this->assertSame(
            ['a' => 'A', 'b' => 'b', 'c' => 'C'],
            Config::get(ConfigTest_MockOption::class, 'map')
        );
        $this->assertSame(
            ['c', 'a', 'b'],
            Config::get(ConfigTest_MockOption::class, 'array')
        );
        $this->assertSame(
            [
                'map'    => ['a' => 'aa', 'b' => 'b', 'c' => 'cc'],
                'array'  => ['cc', 'a', 'b'],
            ],
            Config::get(ConfigTest_MockOption::class, 'parent')
        );
        $this->assertSame(
            ['a' => 'aa', 'b' => 'b', 'c' => 'cc'],
            Config::get(ConfigTest_MockOption::class, 'parent.map')
        );
        $this->assertSame(
            ['cc', 'a', 'b'],
            Config::get(ConfigTest_MockOption::class, 'parent.array')
        );
        $this->assertSame('aa', Config::get(ConfigTest_MockOption::class, 'parent.map.a'));
        $this->assertSame('b', Config::get(ConfigTest_MockOption::class, 'parent.map.b'));
        $this->assertSame('cc', Config::get(ConfigTest_MockOption::class, 'parent.map.c'));
    }

    public function test_get_optionMapReplace()
    {
        $this->assertSame(
            [
                'map'    => ['a' => 'a', 'b' => 'b'],
                'array'  => ['a', 'b'],
                'parent' => [
                    'map'   => ['a' => 'a', 'b' => 'b'],
                    'array' => ['a', 'b'],
                ],
            ],
            Config::get(ConfigTest_MockOption::class)
        );

        Config::framework([
            ConfigTest_MockOption::class => [
                'map='   => ['a' => 'A', 'c' => 'C'],
                'array'  => ['c'],
                'parent' => [
                    'map='   => ['a' => 'aa', 'c' => 'cc'],
                    'array'  => ['cc'],
                ],
            ]
        ]);

        $this->assertSame(
            [
                'map'    => ['a' => 'A', 'c' => 'C'],
                'array'  => ['c', 'a', 'b'],
                'parent' => [
                    'map'    => ['a' => 'aa', 'c' => 'cc'],
                    'array'  => ['cc', 'a', 'b'],
                ],
            ],
            Config::get(ConfigTest_MockOption::class)
        );
        $this->assertSame(
            ['a' => 'A', 'c' => 'C'],
            Config::get(ConfigTest_MockOption::class, 'map')
        );
        $this->assertSame(
            ['c', 'a', 'b'],
            Config::get(ConfigTest_MockOption::class, 'array')
        );
        $this->assertSame(
            [
                'map'    => ['a' => 'aa', 'c' => 'cc'],
                'array'  => ['cc', 'a', 'b'],
            ],
            Config::get(ConfigTest_MockOption::class, 'parent')
        );
        $this->assertSame(
            ['a' => 'aa', 'c' => 'cc'],
            Config::get(ConfigTest_MockOption::class, 'parent.map')
        );
        $this->assertSame(
            ['cc', 'a', 'b'],
            Config::get(ConfigTest_MockOption::class, 'parent.array')
        );
        $this->assertSame('aa', Config::get(ConfigTest_MockOption::class, 'parent.map.a'));
        $this->assertNull(Config::get(ConfigTest_MockOption::class, 'parent.map.b', false));
        $this->assertSame('cc', Config::get(ConfigTest_MockOption::class, 'parent.map.c'));
    }

    public function test_get_optionArrayReplace()
    {
        $this->assertSame(
            [
                'map'    => ['a' => 'a', 'b' => 'b'],
                'array'  => ['a', 'b'],
                'parent' => [
                    'map'   => ['a' => 'a', 'b' => 'b'],
                    'array' => ['a', 'b'],
                ],
            ],
            Config::get(ConfigTest_MockOption::class)
        );

        Config::framework([
            ConfigTest_MockOption::class => [
                'map'    => ['a' => 'A', 'c' => 'C'],
                'array=' => ['c'],
                'parent' => [
                    'map'    => ['a' => 'aa', 'c' => 'cc'],
                    'array=' => ['cc'],
                ],
            ]
        ]);

        $this->assertSame(
            [
                'map'    => ['a' => 'A', 'b' => 'b', 'c' => 'C'],
                'array'  => ['c'],
                'parent' => [
                    'map'    => ['a' => 'aa', 'b' => 'b', 'c' => 'cc'],
                    'array'  => ['cc'],
                ],
            ],
            Config::get(ConfigTest_MockOption::class)
        );
        $this->assertSame(
            ['a' => 'A', 'b' => 'b', 'c' => 'C'],
            Config::get(ConfigTest_MockOption::class, 'map')
        );
        $this->assertSame(
            ['c'],
            Config::get(ConfigTest_MockOption::class, 'array')
        );
        $this->assertSame(
            [
                'map'    => ['a' => 'aa', 'b' => 'b', 'c' => 'cc'],
                'array'  => ['cc'],
            ],
            Config::get(ConfigTest_MockOption::class, 'parent')
        );
        $this->assertSame(
            ['a' => 'aa', 'b' => 'b', 'c' => 'cc'],
            Config::get(ConfigTest_MockOption::class, 'parent.map')
        );
        $this->assertSame(
            ['cc'],
            Config::get(ConfigTest_MockOption::class, 'parent.array')
        );
        $this->assertSame('aa', Config::get(ConfigTest_MockOption::class, 'parent.map.a'));
        $this->assertSame('b', Config::get(ConfigTest_MockOption::class, 'parent.map.b'));
        $this->assertSame('cc', Config::get(ConfigTest_MockOption::class, 'parent.map.c'));
    }

    public function test_get_optionParentReplace()
    {
        $this->assertSame(
            [
                'map'    => ['a' => 'a', 'b' => 'b'],
                'array'  => ['a', 'b'],
                'parent' => [
                    'map'   => ['a' => 'a', 'b' => 'b'],
                    'array' => ['a', 'b'],
                ],
            ],
            Config::get(ConfigTest_MockOption::class)
        );

        Config::framework([
            ConfigTest_MockOption::class => [
                'map'     => ['a' => 'A', 'c' => 'C'],
                'array'   => ['c'],
                'parent=' => [
                    'map'   => ['a' => 'aa', 'c' => 'cc'],
                    'array' => ['cc'],
                ],
            ]
        ]);

        $this->assertSame(
            [
                'map'    => ['a' => 'A', 'b' => 'b', 'c' => 'C'],
                'array'  => ['c', 'a', 'b'],
                'parent' => [
                    'map'    => ['a' => 'aa', 'c' => 'cc'],
                    'array'  => ['cc'],
                ],
            ],
            Config::get(ConfigTest_MockOption::class)
        );
        $this->assertSame(
            ['a' => 'A', 'b' => 'b', 'c' => 'C'],
            Config::get(ConfigTest_MockOption::class, 'map')
        );
        $this->assertSame(
            ['c', 'a', 'b'],
            Config::get(ConfigTest_MockOption::class, 'array')
        );
        $this->assertSame(
            [
                'map'    => ['a' => 'aa', 'c' => 'cc'],
                'array'  => ['cc'],
            ],
            Config::get(ConfigTest_MockOption::class, 'parent')
        );
        $this->assertSame(
            ['a' => 'aa', 'c' => 'cc'],
            Config::get(ConfigTest_MockOption::class, 'parent.map')
        );
        $this->assertSame(
            ['cc'],
            Config::get(ConfigTest_MockOption::class, 'parent.array')
        );
        $this->assertSame('aa', Config::get(ConfigTest_MockOption::class, 'parent.map.a'));
        $this->assertNull(Config::get(ConfigTest_MockOption::class, 'parent.map.b', false));
        $this->assertSame('cc', Config::get(ConfigTest_MockOption::class, 'parent.map.c'));
    }

    public function test_get_optionArrayPrepend()
    {
        $this->assertSame(
            [
                'map'    => ['a' => 'a', 'b' => 'b'],
                'array'  => ['a', 'b'],
                'parent' => [
                    'map'   => ['a' => 'a', 'b' => 'b'],
                    'array' => ['a', 'b'],
                ],
            ],
            Config::get(ConfigTest_MockOption::class)
        );

        Config::framework([
            ConfigTest_MockOption::class => [
                'map'    => ['a' => 'A', 'c' => 'C'],
                'array<' => ['c'],
                'parent' => [
                    'map'    => ['a' => 'aa', 'c' => 'cc'],
                    'array<' => ['cc'],
                ],
            ]
        ]);

        $this->assertSame(
            [
                'map'    => ['a' => 'A', 'b' => 'b', 'c' => 'C'],
                'array'  => ['c', 'a', 'b'],
                'parent' => [
                    'map'    => ['a' => 'aa', 'b' => 'b', 'c' => 'cc'],
                    'array'  => ['cc', 'a', 'b'],
                ],
            ],
            Config::get(ConfigTest_MockOption::class)
        );
        $this->assertSame(
            ['a' => 'A', 'b' => 'b', 'c' => 'C'],
            Config::get(ConfigTest_MockOption::class, 'map')
        );
        $this->assertSame(
            ['c', 'a', 'b'],
            Config::get(ConfigTest_MockOption::class, 'array')
        );
        $this->assertSame(
            [
                'map'    => ['a' => 'aa', 'b' => 'b', 'c' => 'cc'],
                'array'  => ['cc', 'a', 'b'],
            ],
            Config::get(ConfigTest_MockOption::class, 'parent')
        );
        $this->assertSame(
            ['a' => 'aa', 'b' => 'b', 'c' => 'cc'],
            Config::get(ConfigTest_MockOption::class, 'parent.map')
        );
        $this->assertSame(
            ['cc', 'a', 'b'],
            Config::get(ConfigTest_MockOption::class, 'parent.array')
        );
        $this->assertSame('aa', Config::get(ConfigTest_MockOption::class, 'parent.map.a'));
        $this->assertSame('b', Config::get(ConfigTest_MockOption::class, 'parent.map.b'));
        $this->assertSame('cc', Config::get(ConfigTest_MockOption::class, 'parent.map.c'));
    }

    public function test_get_optionMixed()
    {
        $this->assertSame(
            [
                'map'    => ['a' => 'a', 'b' => 'b'],
                'array'  => ['a', 'b'],
                'parent' => [
                    'map'   => ['a' => 'a', 'b' => 'b'],
                    'array' => ['a', 'b'],
                ],
            ],
            Config::get(ConfigTest_MockOption::class)
        );

        Config::framework([
            ConfigTest_MockOption::class => [
                'map='    => ['a' => 'A', 'c' => 'C'],
                'array'   => ['c'],
                'parent'  => [
                    'map'    => ['a' => 'aa', 'c' => 'cc'],
                    'array>' => ['cc'],
                ],
            ]
        ]);

        Config::application([
            ConfigTest_MockOption::class => [
                'map'    => ['d' => 'D'],
                'array=' => ['d'],
                'parent' => [
                    'array' => ['d'],
                    'new'   => 'new',
                ],
            ]
        ]);

        Config::runtime([
            ConfigTest_MockOption::class => [
                'array>' => ['e'],
                'parent' => [
                    'new'   => 'NEW',
                ],
            ]
        ]);

        $this->assertSame(
            [
                'map'    => ['a' => 'A', 'c' => 'C', 'd' => 'D'],
                'array'  => ['d', 'e'],
                'parent' => [
                    'map'   => ['a' => 'aa', 'b' => 'b', 'c' => 'cc'],
                    'array' => ['d', 'a', 'b', 'cc'],
                    'new'   => 'NEW',
                ],
            ],
            Config::get(ConfigTest_MockOption::class)
        );
        $this->assertSame(
            ['a' => 'A', 'c' => 'C', 'd' => 'D'],
            Config::get(ConfigTest_MockOption::class, 'map')
        );
        $this->assertSame(
            ['d', 'e'],
            Config::get(ConfigTest_MockOption::class, 'array')
        );
        $this->assertSame(
            [
                'map'   => ['a' => 'aa', 'b' => 'b', 'c' => 'cc'],
                'array' => ['d', 'a', 'b', 'cc'],
                'new'   => 'NEW',
            ],
            Config::get(ConfigTest_MockOption::class, 'parent')
        );
        $this->assertSame(
            ['a' => 'aa', 'b' => 'b', 'c' => 'cc'],
            Config::get(ConfigTest_MockOption::class, 'parent.map')
        );
        $this->assertSame(
            ['d', 'a', 'b', 'cc'],
            Config::get(ConfigTest_MockOption::class, 'parent.array')
        );
        $this->assertSame(
            'NEW',
            Config::get(ConfigTest_MockOption::class, 'parent.new')
        );
        $this->assertSame('aa', Config::get(ConfigTest_MockOption::class, 'parent.map.a'));
        $this->assertSame('b', Config::get(ConfigTest_MockOption::class, 'parent.map.b'));
        $this->assertSame('cc', Config::get(ConfigTest_MockOption::class, 'parent.map.c'));
    }

    public function test_get_all()
    {
        Config::clear();

        $config = Config::all();
        $this->assertSame([], $config);

        DateTime::setTestNow('2010-01-23');
        $config = Config::all();

        $this->assertSame(
            '2010-01-23',
            $config[DateTime::class]['test_now']
        );
    }

    public function test_has_digitKeyAccessLast()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Invalid config key access, the key 'array.1' contains digit only part.");

        Config::application([
            ConfigTest_Mock::class => [
                'array' => [1, 2, 3],
            ]
        ]);

        $this->assertTrue(Config::has(ConfigTest_Mock::class, 'array'));
        Config::has(ConfigTest_Mock::class, 'array.1');
    }

    public function test_has_digitKeyAccessOnly()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Invalid config key access, the key '1' contains digit only part.");

        Config::has(ConfigTest_Mock::class, '1');
    }

    public function test_has_digitKeyAccessMiddle()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Invalid config key access, the key 'driver.123.dummy' contains digit only part.");

        Config::has(ConfigTest_Mock::class, 'driver.123.dummy');
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
            'driver'   => 'mysql',
            'host'     => 'localhost',
            'port'     => 3306,
            'database' => null,
            'user'     => null,
        ];
    }
}
class ConfigTest_MockOption
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'map'    => ['a' => 'a', 'b' => 'b'],
            'array'  => ['a', 'b'],
            'parent' => [
                'map'    => ['a' => 'a', 'b' => 'b'],
                'array'  => ['a', 'b'],
            ],
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
            'promise_once'  => Config::promise(function () { return \getenv('PROMISE_TEST') ?: 'default'; }),
            'promise_every' => Config::promise(function () { return \getenv('PROMISE_TEST') ?: 'default'; }, false),
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
            'mock_instantiate'     => ConfigTest_MockInstantiate::class,
            'mock_instantiate_arg' => [ConfigTest_MockInstantiate::class, 'arg'],
            'mock_instantiate_map' => [ConfigTest_MockInstantiate::class, 'value' => 'map'],
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
