<?php

declare(strict_types=1);

namespace Rebet\Tests\Http\Session\Storage\Handler;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Rebet\Http\Session\Storage\Handler\MemcachedSessionHandler;
use Rebet\Http\Session\Storage\SessionStorage;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Rebet\Tools\Config\Exception\ConfigNotDefineException;

#[RequiresPhpExtension('memcached')]
class MemcachedSessionHandlerTest extends RebetTestCase
{
    protected function tearDown(): void
    {
        putenv('SESSION_MEMCACHED_DSN');
        parent::tearDown();
    }

    private function ttl(MemcachedSessionHandler $handler): int|null
    {
        $ref = new \ReflectionClass(\Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler::class);
        $ttl = $ref->getProperty('ttl')->getValue($handler);
        return $ttl instanceof \Closure ? $ttl() : $ttl;
    }

    public function test___construct(): void
    {
        $this->assertInstanceOf(MemcachedSessionHandler::class, new MemcachedSessionHandler('memcached://memcached-session:11211'));
    }

    public function test___construct_withPrefixTtlAndOptions(): void
    {
        $handler = new MemcachedSessionHandler('memcached://memcached-session:11211', 'test:s:', 3600, ['persistent_id' => 'test']);
        $this->assertInstanceOf(MemcachedSessionHandler::class, $handler);

        $ref = new \ReflectionClass(\Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler::class);
        $this->assertSame('test:s:', $ref->getProperty('prefix')->getValue($handler));
        $this->assertSame(3600, $this->ttl($handler));
    }

    public function test___construct_defaultPrefix(): void
    {
        $handler = new MemcachedSessionHandler('memcached://memcached-session:11211');

        $ref = new \ReflectionClass(\Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler::class);
        $this->assertSame('rbt-s:', $ref->getProperty('prefix')->getValue($handler));
    }

    public function test___construct_defaultTtlFollowsGcMaxlifetime(): void
    {
        $org = ini_get('session.gc_maxlifetime');
        try {
            $handler = new MemcachedSessionHandler('memcached://memcached-session:11211');
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
        putenv('SESSION_MEMCACHED_DSN=memcached://memcached-session:11211');
        $org = ini_get('session.gc_maxlifetime');
        try {
            Config::application([SessionStorage::class => ['handler' => MemcachedSessionHandler::class]]);
            // SessionStorage creates the handler before applying the options by ini_set.
            $storage = new SessionStorage(['gc_maxlifetime' => 9000]);
            $this->assertSame(9000, $this->ttl($storage->getSaveHandler()->getHandler()));
        } finally {
            ini_set('session.gc_maxlifetime', $org);
        }
    }

    public function test___construct_dsnFromEnv(): void
    {
        putenv('SESSION_MEMCACHED_DSN=memcached://memcached-session:11211');
        $this->assertInstanceOf(MemcachedSessionHandler::class, new MemcachedSessionHandler());
    }

    public function test___construct_dsnNotDefined(): void
    {
        putenv('SESSION_MEMCACHED_DSN');
        $this->expectException(ConfigNotDefineException::class);
        new MemcachedSessionHandler();
    }
}
