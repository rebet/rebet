<?php
namespace Rebet\Tests\Annotation;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Annotation\AnnotatedMethod;
use Rebet\Annotation\AnnotatedProperty;
use Rebet\Routing\Annotation\Channel;
use Rebet\Routing\Annotation\Method;
use Rebet\Routing\Annotation\Where;
use Rebet\Tests\RebetTestCase;

class AnnotatedClassTest extends RebetTestCase
{
    public function test_construct()
    {
        $ac = new AnnotatedClass(AnnotatedClassTest_Mock::class);
        $this->assertInstanceOf(AnnotatedClass::class, $ac);

        $mock = new AnnotatedClassTest_Mock();
        $ac   = new AnnotatedClass($mock);
        $this->assertInstanceOf(AnnotatedClass::class, $ac);
    }

    public function test_of()
    {
        $ac = AnnotatedClass::of(AnnotatedClassTest_Mock::class);
        $this->assertInstanceOf(AnnotatedClass::class, $ac);

        $mock = new AnnotatedClassTest_Mock();
        $ac   = AnnotatedClass::of($mock);
        $this->assertInstanceOf(AnnotatedClass::class, $ac);
    }

    public function test_annotaion()
    {
        $ac      = AnnotatedClass::of(AnnotatedClassTest_Mock::class);
        $channel = $ac->annotation(Channel::class);
        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertSame(['web'], $channel->allows);

        $where = $ac->annotation(Where::class);
        $this->assertInstanceOf(Where::class, $where);
        $this->assertSame(['id' => '[0-9]+', 'code' => '[a-zA-Z]+'], $where->wheres);
    }

    public function test_nonAnnotaion()
    {
        $ac = AnnotatedClass::of(AnnotatedClassTest_Mock::class);
        $this->assertNull($ac->annotation(Method::class, false));
    }

    public function test_annotaions()
    {
        $ac          = AnnotatedClass::of(AnnotatedClassTest_Mock::class);
        $annotations = $ac->annotations();

        $channel = $annotations[0];
        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertSame(['web'], $channel->allows);

        $where = $annotations[1];
        $this->assertInstanceOf(Where::class, $where);
        $this->assertSame(['id' => '[0-9]+', 'code' => '[a-zA-Z]+'], $where->wheres);
    }

    public function test_method()
    {
        $am = AnnotatedClass::of(AnnotatedClassTest_Mock::class)->method('bar');
        $this->assertInstanceOf(AnnotatedMethod::class, $am);
    }

    public function test_property()
    {
        $am = AnnotatedClass::of(AnnotatedClassTest_Mock::class)->property('foo');
        $this->assertInstanceOf(AnnotatedProperty::class, $am);
    }

    public function test_reflector()
    {
        $ac = AnnotatedClass::of(AnnotatedClassTest_Mock::class);
        $this->assertInstanceOf(\ReflectionClass::class, $ac->reflector());
    }
}

/**
 * @Channel("web")
 * @Where({"id": "[0-9]+", "code": "[a-zA-Z]+"})
 */
class AnnotatedClassTest_Mock
{
    public $foo;

    public function bar()
    {
    }
}
