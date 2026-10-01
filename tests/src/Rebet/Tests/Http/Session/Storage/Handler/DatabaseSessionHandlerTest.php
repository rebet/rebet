<?php

declare(strict_types=1);

namespace Rebet\Tests\Http\Session\Storage\Handler;

use Rebet\Database\Dao;
use Rebet\Http\Session\Storage\Handler\DatabaseSessionHandler;
use Rebet\Http\Session\Storage\SessionStorage;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\Config\Config;

class DatabaseSessionHandlerTest extends RebetDatabaseTestCase
{
    private function ttl(DatabaseSessionHandler $handler): int|null
    {
        $ref = new \ReflectionClass(\Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler::class);
        $ttl = $ref->getProperty('ttl')->getValue($handler);
        return $ttl instanceof \Closure ? $ttl() : $ttl;
    }

    public function test___construct(): void
    {
        $this->assertInstanceOf(DatabaseSessionHandler::class, new DatabaseSessionHandler());
    }

    public function test___construct_dbFromArgument(): void
    {
        $this->assertInstanceOf(DatabaseSessionHandler::class, new DatabaseSessionHandler('sqlite'));
        $this->assertSame('sqlite', Dao::current()->name());
    }

    public function test___construct_dbFromConfig(): void
    {
        Config::application([
            Dao::class                    => ['default_db' => 'mysql'],
            DatabaseSessionHandler::class => ['db' => 'sqlite'],
        ]);
        $this->assertInstanceOf(DatabaseSessionHandler::class, new DatabaseSessionHandler());
        $this->assertSame('sqlite', Dao::current()->name());
    }

    public function test___construct_defaultTtlFollowsGcMaxlifetime(): void
    {
        $org = ini_get('session.gc_maxlifetime');
        try {
            $handler = new DatabaseSessionHandler();
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
        $org = ini_get('session.gc_maxlifetime');
        try {
            Config::application([SessionStorage::class => ['handler' => DatabaseSessionHandler::class]]);
            // SessionStorage creates the handler before applying the options by ini_set.
            $storage = new SessionStorage(['gc_maxlifetime' => 9000]);
            $this->assertSame(9000, $this->ttl($storage->getSaveHandler()->getHandler()));
        } finally {
            ini_set('session.gc_maxlifetime', $org);
        }
    }

    public function test___construct_ttlFromOptions(): void
    {
        $handler = new DatabaseSessionHandler(null, ['ttl' => 600]);
        $this->assertSame(600, $this->ttl($handler));
    }
}
