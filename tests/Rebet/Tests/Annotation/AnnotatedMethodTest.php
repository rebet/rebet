<?php
namespace Rebet\Tests\Annotation;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Annotation\AnnotatedMethod;
use Rebet\Routing\Annotation\Channel;
use Rebet\Routing\Annotation\Method;
use Rebet\Routing\Annotation\Where;
use Rebet\Tests\RebetTestCase;

class AnnotatedMethodTest extends RebetTestCase
{
    public function test_construct()
    {
        $rm = new \ReflectionMethod(AnnotatedMethodTest_Mock::class, 'foo');
        $am = new AnnotatedMethod($rm);
        $this->assertInstanceOf(AnnotatedMethod::class, $am);
    }
    
    public function test_of()
    {
        $rm = new \ReflectionMethod(AnnotatedMethodTest_Mock::class, 'foo');
        $am = AnnotatedMethod::of($rm);
        $this->assertInstanceOf(AnnotatedMethod::class, $am);

        $am = AnnotatedMethod::of('foo', AnnotatedMethodTest_Mock::class);
        $this->assertInstanceOf(AnnotatedMethod::class, $am);
        
        $mock = new AnnotatedMethodTest_Mock();
        $am   = AnnotatedMethod::of('foo', $mock);
        $this->assertInstanceOf(AnnotatedMethod::class, $am);
    }
    
    public function test_annotaion()
    {
        $am      = AnnotatedMethod::of('foo', AnnotatedMethodTest_Mock::class);
        $channel = $am->annotation(Channel::class, false);
        $this->assertNull($channel);
        
        $channel = $am->annotation(Channel::class);
        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertSame(['web'], $channel->allows);

        $where = $am->annotation(Where::class);
        $this->assertInstanceOf(Where::class, $where);
        $this->assertSame(['id' => '[0-9]+'], $where->wheres);

        $am      = AnnotatedMethod::of('bar', AnnotatedMethodTest_Mock::class);
        $channel = $am->annotation(Channel::class);
        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertSame(['api'], $channel->allows);

        $where = $am->annotation(Where::class);
        $this->assertNull($where);
    }

    public function test_nonAnnotaion()
    {
        $am = AnnotatedMethod::of('foo', AnnotatedMethodTest_Mock::class);
        $this->assertNull($am->annotation(Method::class, false));
        $this->assertNull($am->annotation(Method::class));
    }
    
    public function test_annotaions()
    {
        $am          = AnnotatedMethod::of('bar', AnnotatedMethodTest_Mock::class);
        $annotations = $am->annotations();

        $channel = $annotations[0];
        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertSame(['api'], $channel->allows);
    }
    
    public function test_declaringClass()
    {
        $ac = AnnotatedMethod::of('foo', AnnotatedMethodTest_Mock::class)->declaringClass();
        $this->assertInstanceOf(AnnotatedClass::class, $ac);
        
        $channel = $ac->annotation(Channel::class);
        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertSame(['web'], $channel->allows);
    }

    public function test_reflector()
    {
        $am = AnnotatedMethod::of('foo', AnnotatedMethodTest_Mock::class);
        $this->assertInstanceOf(\ReflectionMethod::class, $am->reflector());
    }
}

/**
 * @Channel("web")
 */
class AnnotatedMethodTest_Mock
{
    /**
     * @Where({"id": "[0-9]+"})
     */
    public function foo($id)
    {
    }

    /**
     * @Channel("api")
     */
    public function bar()
    {
    }
}
