<?php
namespace Rebet\Tests\Cache;

use Rebet\Cache\Adapter\Symfony\ArrayAdapter;
use Rebet\Cache\Store;
use Rebet\Cache\TagSet;
use Rebet\Tests\RebetCacheTestCase;

class TagSetTest extends RebetCacheTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(TagSet::class, new TagSet(new ArrayAdapter('', 0, true), ['foo']));
    }

    public function test_retrieve()
    {
        $this->eachStore(function (Store $store, $store_name) {
            $msg = ">> [{$store_name}] : ";
            $this->assertSame(null, $store->get('foo'), $msg);
            $this->assertSame('foo', $store->tags('F')->retrieve('foo', '10min', 'foo'), $msg);
            $this->assertSame('foo', $store->get('foo'), $msg);
            $this->assertSame(true, $store->tags('F')->flush(), $msg);
            $this->assertSame(null, $store->get('foo'), $msg);

            $this->assertSame('foo', $store->tags('F', 'B')->retrieve('foo', '10min', function () { return 'foo'; }), $msg);
            $this->assertSame('foo', $store->get('foo'), $msg);
            $this->assertSame(true, $store->tags('F')->flush(), $msg);
            $this->assertSame(null, $store->get('foo'), $msg);
        }, true);
    }

    public function test_put()
    {
        $this->eachStore(function (Store $store, $store_name) {
            $msg = ">> [{$store_name}] : ";
            $this->assertSame(null, $store->get('foo'), $msg);
            $this->assertSame(null, $store->get('bar'), $msg);
            $this->assertSame(null, $store->get('baz'), $msg);

            $this->assertSame(true, $store->tags('F')->put(['foo' => 'FOO'], 100), $msg);
            $this->assertSame(true, $store->tags('B')->put(['bar' => 'BAR', 'baz' => 'BAZ'], 100), $msg);
            $this->assertSame('FOO', $store->get('foo'), $msg);
            $this->assertSame('BAR', $store->get('bar'), $msg);
            $this->assertSame('BAZ', $store->get('baz'), $msg);

            $this->assertSame(true, $store->tags('B')->flush(), $msg);

            $this->assertSame('FOO', $store->get('foo'), $msg);
            $this->assertSame(null, $store->get('bar'), $msg);
            $this->assertSame(null, $store->get('baz'), $msg);
        }, true);
    }

    public function test_flush()
    {
        $this->eachStore(function (Store $store, $store_name) {
            $msg = ">> [{$store_name}] : ";
            $this->assertSame(true, $store->tags('A')->put(['a' => 'A'], 100), $msg);
            $this->assertSame(true, $store->tags('A', 'B')->put(['ab' => 'AB'], 100), $msg);
            $this->assertSame(true, $store->tags('B', 'C')->put(['bc' => 'BC'], 100), $msg);
            $this->assertSame(true, $store->tags('C', 'D')->put(['cd' => 'CD'], 100), $msg);
            $this->assertSame(true, $store->tags('D', 'E')->put(['de' => 'DE'], 100), $msg);
            $this->assertSame(true, $store->tags('E')->put(['e' => 'E'], 100), $msg);
            $this->assertSame(true, $store->has('a'), $msg);
            $this->assertSame(true, $store->has('ab'), $msg);
            $this->assertSame(true, $store->has('bc'), $msg);
            $this->assertSame(true, $store->has('cd'), $msg);
            $this->assertSame(true, $store->has('de'), $msg);
            $this->assertSame(true, $store->has('e'), $msg);

            $this->assertSame(true, $store->tags('A')->flush(), $msg);
            $this->assertSame(false, $store->has('a'), $msg);
            $this->assertSame(false, $store->has('ab'), $msg);
            $this->assertSame(true, $store->has('bc'), $msg);
            $this->assertSame(true, $store->has('cd'), $msg);
            $this->assertSame(true, $store->has('de'), $msg);
            $this->assertSame(true, $store->has('e'), $msg);

            $this->assertSame(true, $store->tags('B', 'D')->flush(), $msg);
            $this->assertSame(false, $store->has('a'), $msg);
            $this->assertSame(false, $store->has('ab'), $msg);
            $this->assertSame(false, $store->has('bc'), $msg);
            $this->assertSame(false, $store->has('cd'), $msg);
            $this->assertSame(false, $store->has('de'), $msg);
            $this->assertSame(true, $store->has('e'), $msg);
        }, true);
    }
}
