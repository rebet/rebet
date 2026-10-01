<?php

declare(strict_types=1);

namespace Rebet\Tests\Http\Session\Storage\Handler;

use Override;
use Rebet\Http\Session\Storage\Handler\MongoDbSessionHandler;
use Rebet\Http\Session\Storage\SessionStorage;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Config;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

class MongoDbSessionHandlerTest extends RebetTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists('MongoDB\Client')) {
            $this->markTestSkipped('MongoDB\Client (mongodb/mongodb package) is not installed.');
        }
    }

    #[Override]
    protected function tearDown(): void
    {
        putenv('SESSION_MONGODB_URI');
        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function options(MongoDbSessionHandler $handler): array
    {
        $ref = new \ReflectionClass(\Symfony\Component\HttpFoundation\Session\Storage\Handler\MongoDbSessionHandler::class);
        return $ref->getProperty('options')->getValue($handler);
    }

    private function namespace(MongoDbSessionHandler $handler): string
    {
        $ref = new \ReflectionClass(\Symfony\Component\HttpFoundation\Session\Storage\Handler\MongoDbSessionHandler::class);
        return $ref->getProperty('namespace')->getValue($handler);
    }

    public function test___construct(): void
    {
        $handler = new MongoDbSessionHandler('mongodb://localhost:27017', null, null, [
            'database'   => 'test_db',
            'collection' => 'test_sessions',
        ]);
        $this->assertInstanceOf(MongoDbSessionHandler::class, $handler);
        $this->assertSame('test_db.test_sessions', $this->namespace($handler));
        $this->assertSame('_id', $this->options($handler)['id_field']);
        $this->assertSame('expires_at', $this->options($handler)['expiry_field']);
    }

    public function test___construct_withUriOptionsAndDriverOptions(): void
    {
        $handler = new MongoDbSessionHandler(
            'mongodb://localhost:27017',
            ['appname' => 'rebet-test'],
            ['typeMap' => ['root' => 'array']],
            ['database' => 'test_db', 'collection' => 'test_sessions', 'id_field' => 'sid'],
        );
        $this->assertInstanceOf(MongoDbSessionHandler::class, $handler);
        $this->assertSame('sid', $this->options($handler)['id_field']);
    }

    public function test___construct_fromConfig(): void
    {
        Config::application([
            MongoDbSessionHandler::class => [
                'uri'         => 'mongodb://localhost:27017',
                'uri_options' => ['appname' => 'rebet-test'],
                'options'     => ['database' => 'config_db', 'collection' => 'config_sessions', 'data_field' => 'payload'],
            ],
        ]);
        $handler = new MongoDbSessionHandler();
        $this->assertSame('config_db.config_sessions', $this->namespace($handler));
        $this->assertSame('payload', $this->options($handler)['data_field']);
        $this->assertSame('time', $this->options($handler)['time_field']);
    }

    public function test___construct_optionsAreMergedIntoConfig(): void
    {
        Config::application([
            MongoDbSessionHandler::class => [
                'options' => ['database' => 'config_db', 'collection' => 'config_sessions', 'data_field' => 'payload'],
            ],
        ]);
        $handler = new MongoDbSessionHandler(null, null, null, ['collection' => 'arg_sessions']);
        $this->assertSame('config_db.arg_sessions', $this->namespace($handler));
        $this->assertSame('payload', $this->options($handler)['data_field']);
    }

    public function test___construct_uriFromEnv(): void
    {
        putenv('SESSION_MONGODB_URI=mongodb://localhost:27017');
        $handler = new MongoDbSessionHandler(null, null, null, ['database' => 'test_db', 'collection' => 'test_sessions']);
        $this->assertInstanceOf(MongoDbSessionHandler::class, $handler);
    }

    private function ttl(MongoDbSessionHandler $handler): int|null
    {
        $ref = new \ReflectionClass(\Symfony\Component\HttpFoundation\Session\Storage\Handler\MongoDbSessionHandler::class);
        $ttl = $ref->getProperty('ttl')->getValue($handler);
        return $ttl instanceof \Closure ? $ttl() : $ttl;
    }

    public function test___construct_defaultTtlFollowsGcMaxlifetime(): void
    {
        $org = ini_get('session.gc_maxlifetime');
        try {
            $handler = new MongoDbSessionHandler('mongodb://localhost:27017', null, null, ['database' => 'test_db', 'collection' => 'test_sessions']);
            // The default ttl is evaluated on each use, so it follows the later change of gc_maxlifetime.
            foreach (['3600' => 3600, '9000' => 9000, '0' => 86400] as $gc_maxlifetime => $expected) {
                ini_set('session.gc_maxlifetime', $gc_maxlifetime);
                $this->assertSame($expected, $this->ttl($handler), "gc_maxlifetime = {$gc_maxlifetime}");
            }
        } finally {
            ini_set('session.gc_maxlifetime', $org);
        }
    }

    public function test___construct_ttlFromOptions(): void
    {
        $handler = new MongoDbSessionHandler('mongodb://localhost:27017', null, null, ['database' => 'test_db', 'collection' => 'test_sessions', 'ttl' => 600]);
        $this->assertSame(600, $this->ttl($handler));
    }

    public function test___construct_defaultDatabaseAndCollection(): void
    {
        $handler = new MongoDbSessionHandler('mongodb://localhost:27017');
        $this->assertSame('rebet.sessions', $this->namespace($handler));
    }

    public function test___construct_databaseAndCollectionCanBeOverriddenOneByOne(): void
    {
        $handler = new MongoDbSessionHandler('mongodb://localhost:27017', null, null, ['database' => 'test_db']);
        $this->assertSame('test_db.sessions', $this->namespace($handler));

        $handler = new MongoDbSessionHandler('mongodb://localhost:27017', null, null, ['collection' => 'test_sessions']);
        $this->assertSame('rebet.test_sessions', $this->namespace($handler));
    }

    public function test___construct_databaseAndCollectionAreRequiredWhenNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MongoDbSessionHandler('mongodb://localhost:27017', null, null, ['database' => null]);
    }

    // ----------------------------------------------------------------------------------------------
    // Tests with the real MongoDB (skipped when the MongoDB server is not reachable)
    // ----------------------------------------------------------------------------------------------

    private const MONGODB_URI = 'mongodb://mongodb:27017';
    private const DATABASE    = 'rebet_test';

    private string $collection = '';

    /**
     * @var string|null the reason why MongoDB is not reachable (null: reachable / not yet checked)
     */
    private static string|null $unreachable = null;
    private static bool $checked            = false;

    /**
     * Prepare the unique collection for the test, or skip the test when MongoDB is not reachable.
     */
    private function prepareMongoDb(): \MongoDB\Client
    {
        $client = new \MongoDB\Client(self::MONGODB_URI, ['serverSelectionTimeoutMS' => 2000, 'connectTimeoutMS' => 2000]);
        if (!self::$checked) {
            // Check the connection only once, so that the skipped tests do not wait for the timeout each time.
            self::$checked = true;
            try {
                $client->selectDatabase('admin')->command(['ping' => 1]);
            } catch (\Throwable $e) {
                self::$unreachable = $e->getMessage();
            }
        }
        if (self::$unreachable !== null) {
            $this->markTestSkipped('MongoDB server is not reachable: ' . self::$unreachable);
        }
        $this->collection = 'sessions_' . bin2hex(random_bytes(4));
        return $client;
    }

    private function realHandler(array $options = [], \Closure|int|null $ttl = null): MongoDbSessionHandler
    {
        $options = array_merge(['database' => self::DATABASE, 'collection' => $this->collection], $options);
        if ($ttl !== null) {
            $options['ttl'] = $ttl;
        }
        $handler = new MongoDbSessionHandler(self::MONGODB_URI, null, null, $options);
        $handler->open('', 'PHPSESSID');
        return $handler;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findSession(\MongoDB\Client $client, string $id, string $id_field = '_id'): array|null
    {
        $doc = $client->selectCollection(self::DATABASE, $this->collection)->findOne([$id_field => $id]);
        return $doc === null ? null : $doc->getArrayCopy();
    }

    private function dropCollection(\MongoDB\Client $client): void
    {
        $client->selectCollection(self::DATABASE, $this->collection)->drop();
    }

    public function test_realMongoDb_writeAndRead(): void
    {
        $client = $this->prepareMongoDb();
        try {
            $handler = $this->realHandler([], 3600);
            $id      = bin2hex(random_bytes(8));

            $this->assertSame('', $handler->read($id));
            $this->assertTrue($handler->write($id, 'foo|s:3:"bar";'));
            $this->assertSame('foo|s:3:"bar";', $handler->read($id));

            $doc = $this->findSession($client, $id);
            $this->assertSame('foo|s:3:"bar";', $doc['data']->getData());
            $this->assertEqualsWithDelta(time() + 3600, $doc['expires_at']->toDateTime()->getTimestamp(), 5);
            $this->assertEqualsWithDelta(time(), $doc['time']->toDateTime()->getTimestamp(), 5);

            // overwrite
            $this->assertTrue($handler->write($id, 'foo|s:3:"baz";'));
            $this->assertSame('foo|s:3:"baz";', $handler->read($id));
            $this->assertSame(1, $client->selectCollection(self::DATABASE, $this->collection)->countDocuments());
        } finally {
            $this->dropCollection($client);
        }
    }

    public function test_realMongoDb_destroy(): void
    {
        $client = $this->prepareMongoDb();
        try {
            $handler = $this->realHandler([], 3600);
            $id      = bin2hex(random_bytes(8));
            $handler->write($id, 'foo|s:3:"bar";');
            $this->assertNotNull($this->findSession($client, $id));

            $this->assertTrue($handler->destroy($id));
            $this->assertNull($this->findSession($client, $id));
            $this->assertSame('', $handler->read($id));
        } finally {
            $this->dropCollection($client);
        }
    }

    public function test_realMongoDb_expiredSessionIsNotReadAndCollectedByGc(): void
    {
        $client = $this->prepareMongoDb();
        try {
            $expired    = $this->realHandler([], -10);
            $alive      = $this->realHandler([], 3600);
            $expired_id = bin2hex(random_bytes(8));
            $alive_id   = bin2hex(random_bytes(8));
            $expired->write($expired_id, 'foo|s:3:"old";');
            $alive->write($alive_id, 'foo|s:3:"new";');

            $this->assertSame('', $alive->read($expired_id));
            $this->assertSame('foo|s:3:"new";', $alive->read($alive_id));

            $alive->gc(0);
            $this->assertNull($this->findSession($client, $expired_id));
            $this->assertNotNull($this->findSession($client, $alive_id));
        } finally {
            $this->dropCollection($client);
        }
    }

    public function test_realMongoDb_updateTimestampExtendsExpiry(): void
    {
        $client = $this->prepareMongoDb();
        try {
            $handler = $this->realHandler([], 3600);
            $id      = bin2hex(random_bytes(8));
            $handler->write($id, 'foo|s:3:"bar";');

            // shorten the expiry, then update timestamp
            $client->selectCollection(self::DATABASE, $this->collection)->updateOne(
                ['_id' => $id],
                ['$set' => ['expires_at' => new \MongoDB\BSON\UTCDateTime((time() + 5) * 1000)]],
            );
            $this->assertTrue($handler->updateTimestamp($id, 'foo|s:3:"bar";'));

            $doc = $this->findSession($client, $id);
            $this->assertEqualsWithDelta(time() + 3600, $doc['expires_at']->toDateTime()->getTimestamp(), 5);
        } finally {
            $this->dropCollection($client);
        }
    }

    public function test_realMongoDb_customFieldNames(): void
    {
        $client = $this->prepareMongoDb();
        try {
            $handler = $this->realHandler(['id_field' => 'sid', 'data_field' => 'payload', 'time_field' => 'saved_at', 'expiry_field' => 'expire_at'], 3600);
            $id      = bin2hex(random_bytes(8));
            $handler->write($id, 'foo|s:3:"bar";');

            $doc = $this->findSession($client, $id, 'sid');
            $this->assertSame('foo|s:3:"bar";', $doc['payload']->getData());
            $this->assertArrayHasKey('saved_at', $doc);
            $this->assertArrayHasKey('expire_at', $doc);
            $this->assertSame('foo|s:3:"bar";', $handler->read($id));
        } finally {
            $this->dropCollection($client);
        }
    }

    public function test_realMongoDb_viaSessionStorageWithGcMaxlifetimeOption(): void
    {
        $client = $this->prepareMongoDb();
        $org    = ini_get('session.gc_maxlifetime');
        putenv('SESSION_MONGODB_URI=' . self::MONGODB_URI);
        try {
            Config::application([
                SessionStorage::class        => ['handler' => MongoDbSessionHandler::class],
                MongoDbSessionHandler::class => ['options' => ['database' => self::DATABASE, 'collection' => $this->collection]],
            ]);
            $storage = new SessionStorage(['use_cookies' => 0, 'gc_maxlifetime' => 9000]);
            $storage->registerBag(new AttributeBag());
            $id = bin2hex(random_bytes(8));
            $storage->setId($id);
            $storage->start();
            $id = $storage->getId(); // An unknown id may be regenerated by the strict mode of session.
            $storage->getBag('attributes')->set('user', 'foo');
            $storage->save();

            // The ttl follows the `gc_maxlifetime` option of SessionStorage that is applied after the handler is created.
            $doc = $this->findSession($client, $id);
            $this->assertNotNull($doc);
            $this->assertEqualsWithDelta(time() + 9000, $doc['expires_at']->toDateTime()->getTimestamp(), 5);
        } finally {
            ini_set('session.gc_maxlifetime', $org);
            $this->dropCollection($client);
        }
    }
}
