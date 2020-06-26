<?php
namespace Rebet\Tests\Validation;

use Rebet\Common\Describable;
use Rebet\Common\Reflector;
use Rebet\Tests\RebetTestCase;

class DescribableTest extends RebetTestCase
{
    public $src;
    public $dest_a;
    public $dest_b;
    public $dest_array;

    public function setUp()
    {
        parent::setUp();
        $this->src      = new DescribableTest_MockA();
        $this->src->foo = 'foo';
        $this->src->bar = 'bar';
        Reflector::set($this->src, 'protected', 'protected', true);
        Reflector::set($this->src, 'private', 'private', true);

        $this->dest_a     = new DescribableTest_MockA();
        $this->dest_b     = new DescribableTest_MockB();
        $this->dest_array = [
            'foo' => null,
            'baz' => null,
        ];
    }

    public function test_inject()
    {
        $this->assertNull($this->dest_a->foo);
        $this->assertNull($this->dest_a->bar);
        $this->assertNull(Reflector::get($this->dest_a, 'protected', null, true));
        $this->assertNull(Reflector::get($this->dest_a, 'private', null, true));
        $this->src->inject($this->dest_a);
        $this->assertSame('foo', $this->dest_a->foo);
        $this->assertSame('bar', $this->dest_a->bar);
        $this->assertSame('protected', Reflector::get($this->dest_a, 'protected', null, true));
        $this->assertSame('private', Reflector::get($this->dest_a, 'private', null, true));

        $this->assertNull($this->dest_b->bar);
        $this->assertNull($this->dest_b->baz);
        $this->assertNull(Reflector::get($this->dest_b, 'protected', null, true));
        $this->assertNull(Reflector::get($this->dest_b, 'private', null, true));
        $this->src->inject($this->dest_b);
        $this->assertNull($this->dest_b->foo ?? null);
        $this->assertSame('bar', $this->dest_b->bar);
        $this->assertNull($this->dest_b->baz);
        $this->assertNull(Reflector::get($this->dest_b, 'protected', null, true));
        $this->assertNull(Reflector::get($this->dest_b, 'private', null, true));

        $this->assertNull($this->dest_array['foo']);
        $this->assertNull($this->dest_array['baz']);
        $this->src->inject($this->dest_array);
        $this->assertSame('foo', $this->dest_array['foo']);
        $this->assertNull($this->dest_array['bar'] ?? null);
        $this->assertNull($this->dest_array['baz']);
        $this->assertNull($this->dest_array['protected'] ?? null);
        $this->assertNull($this->dest_array['private'] ?? null);
    }

    public function test_describe()
    {
        $dest_a = $this->src->describe(DescribableTest_MockA::class);
        $this->assertSame('foo', $dest_a->foo);
        $this->assertSame('bar', $dest_a->bar);
        $this->assertSame('protected', Reflector::get($dest_a, 'protected', null, true));
        $this->assertSame('private', Reflector::get($dest_a, 'private', null, true));
    }

    public function test_inject_optionIncludes()
    {
        $this->src->inject($this->dest_a, ['includes' => ['bar']]);
        $this->assertNull($this->dest_a->foo);
        $this->assertSame('bar', $this->dest_a->bar);
        $this->assertNull(Reflector::get($this->dest_a, 'protected', null, true));
        $this->assertNull(Reflector::get($this->dest_a, 'private', null, true));
    }

    public function test_inject_optionExcludes()
    {
        $this->src->inject($this->dest_a, ['excludes' => ['bar']]);
        $this->assertSame('foo', $this->dest_a->foo);
        $this->assertNull($this->dest_a->bar);
        $this->assertSame('protected', Reflector::get($this->dest_a, 'protected', null, true));
        $this->assertSame('private', Reflector::get($this->dest_a, 'private', null, true));
    }

    public function test_inject_optionAliasesOneway()
    {
        $this->src->inject($this->dest_a, ['aliases' => ['foo' => 'bar']]);
        $this->assertSame('bar', $this->dest_a->foo);
        $this->assertSame('bar', $this->dest_a->bar);
    }

    public function test_inject_optionAliasesNull()
    {
        $this->src->inject($this->dest_a, ['aliases' => ['foo' => null]]);
        $this->assertNull($this->dest_a->foo);
        $this->assertSame('bar', $this->dest_a->bar);
    }

    public function test_inject_optionAliasesCross()
    {
        $this->src->inject($this->dest_a, ['aliases' => ['foo' => 'bar', 'bar' => 'foo']]);
        $this->assertSame('bar', $this->dest_a->foo);
        $this->assertSame('foo', $this->dest_a->bar);
    }

    public function test_inject_optionAliasesInvalid()
    {
        $this->src->inject($this->dest_a, ['aliases' => ['foo' => 'invalid']]);
        $this->assertNull($this->dest_a->foo);
        $this->assertSame('bar', $this->dest_a->bar);
    }
}

class DescribableTest_MockA
{
    use Describable;
    public $foo = null;
    public $bar = null;

    protected $protected = null;
    private $private     = null;
}

class DescribableTest_MockB
{
    public $bar = null;
    public $baz = null;

    protected $protected = null;
    private $private     = null;
}
