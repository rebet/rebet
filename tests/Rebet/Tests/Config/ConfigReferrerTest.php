<?php
namespace Rebet\Tests\Config;

use Rebet\Tests\RebetTestCase;
use Rebet\Config\Config;
use Rebet\Config\Configable;
use Rebet\Config\ConfigReferrer;

class ConfigReferrerTestMock {
    use Configable;
    public static function defaultConfig() {
        return [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => null,
            'user' => Config::refer(ConfigReferrerTestMockNest::class, 'user'),
        ];
    }
}
class ConfigReferrerTestMockNest {
    use Configable;
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
        $this->ref_driver   = new ConfigReferrer(ConfigReferrerTestMock::class, 'driver');
        $this->ref_database = new ConfigReferrer(ConfigReferrerTestMock::class, 'database');
        $this->ref_user     = new ConfigReferrer(ConfigReferrerTestMock::class, 'user', 'default_user');
    }

    public function test_get() {
        $this->assertSame('mysql', $this->ref_driver->get());
        $this->assertNull($this->ref_database->get());
        $this->assertNull(ConfigReferrerTestMock::config('user', false));
        $this->assertSame('default_user', ConfigReferrerTestMock::config('user', false, 'default_user'));
        $this->assertSame('default_user', $this->ref_user->get());

        Config::framework([
            ConfigReferrerTestMock::class => [
                'driver' => 'sqlite',
                'database' => 'test_db',
            ],
            ConfigReferrerTestMockNest::class => [
                'user' => 'test',
            ]
        ]);

        $this->assertSame('sqlite', $this->ref_driver->get());
        $this->assertSame('test_db', $this->ref_database->get());
        $this->assertSame('test', $this->ref_user->get());
        $this->assertSame('test', ConfigReferrerTestMock::config('user', false));
        $this->assertSame('test', ConfigReferrerTestMock::config('user', false, 'default_user'));
    }
}
