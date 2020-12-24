<?php
namespace Rebet\Tests\Cache\Adapter\Symfony;

use Rebet\Cache\Adapter\Symfony\RedisAdapter;
use Rebet\Tests\RebetTestCase;

/**
 * # Install Redis For Windows.
 * 1. Get redis for windows (not official) from Microsoft Open Tech
 *    https://github.com/microsoftarchive/redis/releases
 * 2. Download and install redis using Redis-x64-*.msi
 *
 * # Setup PhpRedis PHP Extension for Windows
 * 1. Get PhpRedis dll from PECL
 *    https://pecl.php.net/package/redis/
 * 2. Copy `php_redis.dll` to `{PHP_HOME}/ext`
 * 3. Edit php.ini
 *    ```
 *    extension=php_redis.dll
 *    ```
 *
 * # Setup Predis
 * 1. Install using composer
 *    composer require --dev predis/predis
 *
 * @requires extension apcu
 */
class RedisAdapterTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(RedisAdapter::class, new RedisAdapter('redis://redis'));
    }
}
