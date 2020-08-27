<?php
namespace Rebet\Tests\Cache\Adapter\Symfony;

use Rebet\Cache\Adapter\Symfony\ApcuAdapter;
use Rebet\Tests\RebetTestCase;

/**
 * # Setup APCu PHP Extension
 * 1. Get APCu dll from PECL
 *    https://pecl.php.net/package/APCu/5.1.18/windows
 * 2. Copy `php_apcu.dll` to `{PHP_HOME}/ext`
 * 3. Edit php.ini
 *    ```
 *    extension=php_apcu.dll
 *    [apcu]
 *    apc.enabled=1
 *    apc.shm_size=32M
 *    apc.ttl=7200
 *    apc.enable_cli=1
 *    apc.serializer=php
 *    ```
 *
 * @requires extension apcu
 */
class ApcuAdapterTest extends RebetTestCase
{
    public function setUp()
    {
        if (!ApcuAdapter::isSupported()) {
            $this->markTestSkipped('APCu is not enabled.');
        }

        parent::setUp();
    }

    public function test___construct()
    {
        $this->assertInstanceOf(ApcuAdapter::class, new ApcuAdapter());
        $this->assertInstanceOf(ApcuAdapter::class, new ApcuAdapter('', 5));
        $this->assertInstanceOf(ApcuAdapter::class, new ApcuAdapter('', '5min'));
    }
}
