<?php

declare(strict_types=1);

namespace Rebet\Tests\Http\Session\Storage\Handler;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Rebet\Http\Session\Storage\Handler\RedisSessionHandler;
use Rebet\Http\Session\Storage\SessionStorage;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Config\Exception\ConfigNotDefineException;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

#[RequiresPhpExtension('redis')]
class RedisSessionHandlerTest extends RebetTestCase
{
    private const DSN = 'redis://redis-session/0?lazy=1';

    protected function tearDown(): void
    {
        putenv('SESSION_REDIS_DSN');
        parent::tearDown();
    }

    private function ttl(RedisSessionHandler $handler): int|null
    {
        $ref = new \ReflectionClass(\Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler::class);
        $ttl = $ref->getProperty('ttl')->getValue($handler);
        return $ttl instanceof \Closure ? $ttl() : $ttl;
    }

    private function prefix(RedisSessionHandler $handler): string
    {
        $ref = new \ReflectionClass(\Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler::class);
        return $ref->getProperty('prefix')->getValue($handler);
    }

    public function test___construct(): void
    {
        $this->assertInstanceOf(RedisSessionHandler::class, new RedisSessionHandler(self::DSN));
    }

    public function test___construct_withPrefixTtlAndOptions(): void
    {
        $handler = new RedisSessionHandler(self::DSN, 'test:s:', 3600, ['timeout' => 5]);
        $this->assertSame('test:s:', $this->prefix($handler));
        $this->assertSame(3600, $this->ttl($handler));
    }

    public function test___construct_defaultPrefix(): void
    {
        $this->assertSame('rbt-s:', $this->prefix(new RedisSessionHandler(self::DSN)));
    }

    public function test___construct_defaultTtlFollowsGcMaxlifetime(): void
    {
        $org = ini_get('session.gc_maxlifetime');
        try {
            $handler = new RedisSessionHandler(self::DSN);
            // The default ttl is evaluated on each use, so it follows the later change of gc_maxlifetime.
            foreach (['3600' => 3600, '9000' => 9000, '0' => 86400] as $gc_maxlifetime => $expected) {
                ini_set('session.gc_maxlifetime', $gc_maxlifetime);
                $this->assertSame($expected, $this->ttl($handler), "gc_maxlifetime = {$gc_maxlifetime}");
            }
        } finally {
            ini_set('session.gc_maxlifetime', $org);
        }
    }

    public function test___construct_ttlFollowsGcMaxlifetimeOptionOfSessionStorage(): void
    {
        putenv('SESSION_REDIS_DSN=' . self::DSN);
        $org = ini_get('session.gc_maxlifetime');
        try {
            Config::application([SessionStorage::class => ['handler' => RedisSessionHandler::class]]);
            // SessionStorage creates the handler before applying the options by ini_set.
            $storage = new SessionStorage(['gc_maxlifetime' => 9000]);
            $this->assertSame(9000, $this->ttl($storage->getSaveHandler()->getHandler()));
        } finally {
            ini_set('session.gc_maxlifetime', $org);
        }
    }

    public function test___construct_dsnFromEnv(): void
    {
        putenv('SESSION_REDIS_DSN=' . self::DSN);
        $this->assertInstanceOf(RedisSessionHandler::class, new RedisSessionHandler());
    }

    public function test___construct_dsnNotDefined(): void
    {
        putenv('SESSION_REDIS_DSN');
        $this->expectException(ConfigNotDefineException::class);
        new RedisSessionHandler();
    }

    // ----------------------------------------------------------------------------------------------
    // Tests with the real Redis (skipped when the Redis server is not reachable)
    // ----------------------------------------------------------------------------------------------

    private const REAL_DSN = 'redis://redis-session/0';

    private string $prefix = '';

    /**
     * @var string|null the reason why Redis is not reachable (null: reachable / not yet checked)
     */
    private static string|null $unreachable = null;
    private static bool $checked            = false;

    /**
     * Prepare the unique key prefix for the test, or skip the test when Redis is not reachable.
     */
    private function prepareRedis(): \Redis
    {
        $redis = new \Redis();
        if (!self::$checked) {
            // Check the connection only once, so that the skipped tests do not wait for the timeout each time.
            self::$checked = true;
            try {
                $redis->connect('redis-session', 6379, 2.0);
                $redis->ping();
            } catch (\Throwable $e) {
                self::$unreachable = $e->getMessage();
            }
        }
        if (self::$unreachable !== null) {
            $this->markTestSkipped('Redis server is not reachable: ' . self::$unreachable);
        }
        if (!$redis->isConnected()) {
            $redis->connect('redis-session', 6379, 2.0);
        }
        $this->prefix = 'test:sessions_' . bin2hex(random_bytes(4)) . ':';
        return $redis;
    }

    private function cleanUp(\Redis $redis): void
    {
        foreach ($redis->keys($this->prefix . '*') as $key) {
            $redis->del($key);
        }
    }

    private function realHandler(\Closure|int|null $ttl = null, array|null $options = null): RedisSessionHandler
    {
        $handler = new RedisSessionHandler(self::REAL_DSN, $this->prefix, $ttl, $options);
        $handler->open('', 'PHPSESSID');
        return $handler;
    }

    public function test_realRedis_writeAndRead(): void
    {
        $redis = $this->prepareRedis();
        try {
            $handler = $this->realHandler(3600);
            $id      = bin2hex(random_bytes(8));

            $this->assertSame('', $handler->read($id));
            $this->assertTrue($handler->write($id, 'foo|s:3:"bar";'));
            $this->assertSame('foo|s:3:"bar";', $handler->read($id));

            $this->assertSame('foo|s:3:"bar";', $redis->get($this->prefix . $id));
            $this->assertEqualsWithDelta(3600, $redis->ttl($this->prefix . $id), 5);

            // overwrite
            $this->assertTrue($handler->write($id, 'foo|s:3:"baz";'));
            $this->assertSame('foo|s:3:"baz";', $handler->read($id));
            $this->assertCount(1, $redis->keys($this->prefix . '*'));
        } finally {
            $this->cleanUp($redis);
        }
    }

    public function test_realRedis_destroy(): void
    {
        $redis = $this->prepareRedis();
        try {
            $handler = $this->realHandler(3600);
            $id      = bin2hex(random_bytes(8));
            $handler->write($id, 'foo|s:3:"bar";');
            $this->assertSame(1, $redis->exists($this->prefix . $id));

            $this->assertTrue($handler->destroy($id));
            $this->assertSame(0, $redis->exists($this->prefix . $id));
            $this->assertSame('', $handler->read($id));
        } finally {
            $this->cleanUp($redis);
        }
    }

    public function test_realRedis_expiredSessionIsNotRead(): void
    {
        $redis = $this->prepareRedis();
        try {
            $handler = $this->realHandler(1);
            $id      = bin2hex(random_bytes(8));
            $handler->write($id, 'foo|s:3:"bar";');
            $this->assertSame('foo|s:3:"bar";', $handler->read($id));

            sleep(2);
            $this->assertSame(0, $redis->exists($this->prefix . $id));
            $this->assertSame('', $handler->read($id));
        } finally {
            $this->cleanUp($redis);
        }
    }

    public function test_realRedis_updateTimestamp(): void
    {
        $redis = $this->prepareRedis();
        try {
            $handler = $this->realHandler(3600);
            $id      = bin2hex(random_bytes(8));
            $handler->write($id, 'foo|s:3:"bar";');
            $redis->expire($this->prefix . $id, 5);
            $this->assertEqualsWithDelta(5, $redis->ttl($this->prefix . $id), 2);

            $this->assertTrue($handler->updateTimestamp($id, 'foo|s:3:"bar";'));
            $this->assertEqualsWithDelta(3600, $redis->ttl($this->prefix . $id), 5);
        } finally {
            $this->cleanUp($redis);
        }
    }

    public function test_realRedis_viaSessionStorageWithGcMaxlifetimeOption(): void
    {
        $redis = $this->prepareRedis();
        $org   = ini_get('session.gc_maxlifetime');
        putenv('SESSION_REDIS_DSN=' . self::REAL_DSN);
        try {
            Config::application([
                SessionStorage::class      => ['handler' => RedisSessionHandler::class],
                RedisSessionHandler::class => ['prefix' => $this->prefix],
            ]);
            $storage = new SessionStorage(['use_cookies' => 0, 'gc_maxlifetime' => 9000]);
            $storage->registerBag(new AttributeBag());
            $storage->setId(bin2hex(random_bytes(8)));
            $storage->start();
            $id = $storage->getId(); // An unknown id may be regenerated by the strict mode of session.
            $storage->getBag('attributes')->set('user', 'foo');
            $storage->save();

            // The ttl follows the `gc_maxlifetime` option of SessionStorage that is applied after the handler is created.
            $this->assertSame(1, $redis->exists($this->prefix . $id));
            $this->assertEqualsWithDelta(9000, $redis->ttl($this->prefix . $id), 5);
        } finally {
            ini_set('session.gc_maxlifetime', $org);
            $this->cleanUp($redis);
        }
    }
}
