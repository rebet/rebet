<?php
namespace Rebet\Tests\Config;

use Rebet\Tests\RebetTestCase;
use Rebet\Config\Config;
use Rebet\Config\Configurable;
use Rebet\Config\ConfigReferrer;

class ConfigReferrerTest_Mock {
    use Configurable;
    public static function defaultConfig() {
        return [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => null,
            'user' => Config::refer(ConfigReferrerTest_MockOrigin::class, 'user'),
        ];
    }
}
class ConfigReferrerTest_MockOrigin {
    use Configurable;
    public static function defaultConfig() {
        return [
            'user' => null,
        ];
    }
}

class ConfigReferrerTest extends RebetTestCase {

    private $ref_driver;
    private $ref_database;
    private $ref_user;

    public function setUp() {
        Config::clear();
        $this->ref_driver   = new ConfigReferrer(ConfigReferrerTest_Mock::class, 'driver');
        $this->ref_database = new ConfigReferrer(ConfigReferrerTest_Mock::class, 'database');
        $this->ref_user     = new ConfigReferrer(ConfigReferrerTest_Mock::class, 'user', 'default_user');
    }

    public function test_get() {
        $this->assertSame('mysql', $this->ref_driver->get());
        $this->assertNull($this->ref_database->get());
        $this->assertNull(ConfigReferrerTest_Mock::config('user', false));
        $this->assertSame('default_user', ConfigReferrerTest_Mock::config('user', false, 'default_user'));
        $this->assertSame('default_user', $this->ref_user->get());

        Config::framework([
            ConfigReferrerTest_Mock::class => [
                'driver' => 'sqlite',
                'database' => 'test_db',
            ],
            ConfigReferrerTest_MockOrigin::class => [
                'user' => 'test',
            ]
        ]);

        $this->assertSame('sqlite', $this->ref_driver->get());
        $this->assertSame('test_db', $this->ref_database->get());
        $this->assertSame('test', $this->ref_user->get());
        $this->assertSame('test', ConfigReferrerTest_Mock::config('user', false));
        $this->assertSame('test', ConfigReferrerTest_Mock::config('user', false, 'default_user'));
    }
}
