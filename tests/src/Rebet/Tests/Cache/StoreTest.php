<?php
namespace Rebet\Tests\Cache;

use Rebet\Cache\Adapter\Symfony\ArrayAdapter;
use Rebet\Cache\Store;
use Rebet\Cache\TagSet;
use Rebet\Tests\RebetCacheTestCase;
use Rebet\Tools\Utility\Securities;

class StoreTest extends RebetCacheTestCase
{
    public function test_checkSymfonyCachePdoAdapterCreateTableWhenUseSqlsrvPdo()
    {
        // If this test will be fail, that means [@see https://github.com/symfony/symfony/issues/39793] is fixed.
        // In this case remove this test, and remove comment syntax of 'pdo-sqlsrv' store settings in RebetCacheTestCase.
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage("SQLSTATE[IMSSP]: This function is not implemented by this driver.");

        $pdo = new \PDO('sqlsrv:server=sqlsrv;database=rebet', 'rebet', 'rebet');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $adapter = new \Symfony\Component\Cache\Adapter\PdoAdapter($pdo);
        $adapter->createTable();
    }

    public function test___construct()
    {
        $this->assertInstanceOf(Store::class, new Store('test', new ArrayAdapter()));
    }

    public function test_name()
    {
        $store = new Store('test', new ArrayAdapter());
        $this->assertSame('test', $store->name());
    }

    public function test_adapter()
    {
        $store = new Store('test', $adapter = new ArrayAdapter());
        $this->assertSame($adapter, $store->adapter());
    }

    public function test_retrieve()
    {
        $this->eachStore(function (Store $store, $msg) {
            $this->assertSame(null, $store->get('foo'), $msg);
            $this->assertSame('foo', $store->retrieve('foo', '10min', 'foo'), $msg);
            $this->assertSame('foo', $store->get('foo'), $msg);
            $this->assertSame('foo', $store->retrieve('foo', '10min', 'FOO'), $msg);

            $this->assertNull($store->get('bar'), $msg);
            $value_1 = $store->retrieve('bar', 1, function () { return Securities::randomHash(); });
            $value_2 = $store->retrieve('bar', 1, function () { return Securities::randomHash(); });
            $this->assertSame($value_1, $store->get('bar'), $msg);
            $this->assertSame($value_1, $value_2, $msg);

            for ($i = 0 ; $store->has('bar') && $i < 30 ; $i++) {
                usleep(100000);
            }

            $value_3 = $store->retrieve('bar', 1, function () { return Securities::randomHash(); });
            $this->assertSame($value_3, $store->get('bar'), $msg);
            $this->assertNotSame($value_1, $value_3, $msg);
        });
    }

    public function test_getAndPut()
    {
        $this->eachStore(function (Store $store, $msg) {
            $this->assertSame(null, $store->get('foo'), $msg);
            $this->assertSame(['foo' => null, 'bar' => null], $store->get('foo', 'bar'), $msg);

            $this->assertSame(true, $store->put(['foo' => 'FOO'], 100), $msg);
            $this->assertSame('FOO', $store->get('foo'), $msg);
            $this->assertSame(['foo' => 'FOO', 'bar' => null], $store->get('foo', 'bar'), $msg);
            $this->assertSame(['bar' => null, 'foo' => 'FOO'], $store->get('bar', 'foo'), $msg);

            $this->assertSame(true, $store->put(['bar' => 'BAR'], 100), $msg);
            $this->assertSame('FOO', $store->get('foo'), $msg);
            $this->assertSame(['foo' => 'FOO', 'bar' => 'BAR'], $store->get('foo', 'bar'), $msg);
            $this->assertSame(['bar' => 'BAR', 'foo' => 'FOO'], $store->get('bar', 'foo'), $msg);

            $this->assertSame(true, $store->put(['baz' => 'BAZ', 'qux' => 'QUX'], 100), $msg);
            $this->assertSame(['qux' => 'QUX', 'foo' => 'FOO', 'baz' => 'BAZ'], $store->get('qux', 'foo', 'baz'), $msg);

            $this->assertSame(true, $store->put(['foo' => 'foo'], 100), $msg);
            $this->assertSame('foo', $store->get('foo'), $msg);
        });
    }

    public function test_has()
    {
        $this->eachStore(function (Store $store, $msg) {
            $this->assertSame(false, $store->has('foo'), $msg);
            $this->assertSame(true, $store->put(['foo' => 'FOO'], 1), $msg);
            $this->assertSame(true, $store->has('foo'), $msg);

            for ($i = 0 ; $store->has('foo') && $i < 30 ; $i++) {
                usleep(100000);
            }

            $this->assertSame(false, $store->has('foo'), $msg);
            $this->assertSame(null, $store->get('foo'), $msg);
        });
    }

    public function test_pull()
    {
        $this->eachStore(function (Store $store, $msg) {
            $this->assertSame(false, $store->has('foo'), $msg);
            $this->assertSame(true, $store->put(['foo' => 'FOO'], 100), $msg);
            $this->assertSame(true, $store->has('foo'), $msg);
            $this->assertSame('FOO', $store->pull('foo'), $msg);
            $this->assertSame(false, $store->has('foo'), $msg);

            $this->assertSame(true, $store->put(['foo' => 'FOO', 'bar' => 'BAR', 'baz' => 'BAZ', 'qux' => 'QUX'], 100), $msg);
            $this->assertSame(true, $store->has('foo'), $msg);
            $this->assertSame(true, $store->has('bar'), $msg);
            $this->assertSame(true, $store->has('baz'), $msg);
            $this->assertSame(true, $store->has('qux'), $msg);
            $this->assertSame(['baz' => 'BAZ', 'bar' => 'BAR', 'foo' => 'FOO'], $store->pull('baz', 'bar', 'foo'), $msg);
            $this->assertSame(false, $store->has('foo'), $msg);
            $this->assertSame(false, $store->has('bar'), $msg);
            $this->assertSame(false, $store->has('baz'), $msg);
            $this->assertSame(true, $store->has('qux'), $msg);
        });
    }

    public function test_delete()
    {
        $this->eachStore(function (Store $store, $msg) {
            $this->assertSame(false, $store->has('foo'), $msg);
            $this->assertSame(true, $store->put(['foo' => 'FOO'], 100), $msg);
            $this->assertSame(true, $store->has('foo'), $msg);
            $this->assertSame(true, $store->delete('foo'), $msg);
            $this->assertSame(false, $store->has('foo'), $msg);

            $this->assertSame(true, $store->put(['foo' => 'FOO', 'bar' => 'BAR', 'baz' => 'BAZ'], 100), $msg);
            $this->assertSame(true, $store->has('foo'), $msg);
            $this->assertSame(true, $store->has('bar'), $msg);
            $this->assertSame(true, $store->has('baz'), $msg);
            $this->assertSame(true, $store->delete('baz', 'bar'), $msg);
            $this->assertSame(true, $store->has('foo'), $msg);
            $this->assertSame(false, $store->has('bar'), $msg);
            $this->assertSame(false, $store->has('baz'), $msg);
        });
    }

    public function test_flush()
    {
        $this->eachStore(function (Store $store, $msg) {
            $this->assertSame(true, $store->put(['foo' => 'FOO', 'bar' => 'BAR', 'baz' => 'BAZ'], 100), $msg);
            $this->assertSame(true, $store->has('foo'), $msg);
            $this->assertSame(true, $store->has('bar'), $msg);
            $this->assertSame(true, $store->has('baz'), $msg);
            $this->assertSame(true, $store->flush(), $msg);
            $this->assertSame(false, $store->has('foo'), $msg);
            $this->assertSame(false, $store->has('bar'), $msg);
            $this->assertSame(false, $store->has('baz'), $msg);
        });
    }

    public function test_prune()
    {
        return $this->eachStore(function (Store $store, $msg) {
            $this->assertSame(true, $store->tags('test')->put(['foo' => 'FOO'], 100), $msg);
            $this->assertSame(true, $store->has('foo'), $msg);
            $this->assertSame(true, $store->tags('test')->flush(), $msg);
            $this->assertSame(false, $store->has('foo'), $msg);
            $store->prune();
            $this->assertSame(false, $store->has('foo'), $msg);
        }, true);
    }

    public function test_tags()
    {
        $this->eachStore(function (Store $store, $msg) {
            $this->assertInstanceOf(TagSet::class, $store->tags('foo'), $msg);
            $this->assertInstanceOf(TagSet::class, $store->tags('foo', 'bar'), $msg);
        }, true);
    }
}
