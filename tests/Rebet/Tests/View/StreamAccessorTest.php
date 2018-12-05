<?php
namespace Rebet\Tests\View;

use Rebet\DateTime\DateTime;
use Rebet\Tests\Mock\Gender;
use Rebet\Tests\RebetTestCase;
use Rebet\View\StreamAccessor;

class StreamAccessorTest extends RebetTestCase
{
    private $null;
    private $int;
    private $float;
    private $string;
    private $object;
    private $datetime_o;
    private $datetime_s;
    private $array;
    private $map;
    private $destructive;

    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2001/02/03 04:05:06');
        $this->null       = StreamAccessor::valueOf(null);
        $this->int        = StreamAccessor::valueOf(123);
        $this->float      = StreamAccessor::valueOf(1234.5678);
        $this->string     = StreamAccessor::valueOf("Hello Rebet");
        $this->text       = StreamAccessor::valueOf("Hello\nRebet");
        $this->html       = StreamAccessor::valueOf("<h1>Hello Rebet</h1>");
        $this->json       = StreamAccessor::valueOf("[1 ,2, 3]");
        $this->object     = StreamAccessor::valueOf(Gender::MALE());
        $this->datetime_o = StreamAccessor::valueOf(DateTime::now());
        $this->datetime_s = StreamAccessor::valueOf('2001/02/03 04:05:06');
        $this->array      = StreamAccessor::valueOf([1, 2, 3]);
        $this->map        = StreamAccessor::valueOf([
            'foo'    => 'FOO',
            'parent' => [
                'child' => [
                    'bar' => 'BAR',
                ],
            ],
            'number' => 123,
            'gender' => Gender::MALE(),
        ]);
        $this->destructive = StreamAccessor::valueOf(new StreamAccessorTest_DestructiveMock());
    }

    public function test_valueOf()
    {
        $this->assertInstanceOf(StreamAccessor::class, StreamAccessor::valueOf(123));
    }

    public function test_promise()
    {
        $source = null;
        $value  = StreamAccessor::promise(function () use (&$source) { return $source; });
        $this->assertInstanceOf(StreamAccessor::class, $value);

        $source = 1;
        $this->assertSame(1, $value->origin());

        $source = 2;
        $this->assertSame(1, $value->origin());
    }

    public function test_origin()
    {
        $this->assertSame(123, $this->int->origin());
        $this->assertSame("Hello Rebet", $this->string->origin());
        $this->assertSame(Gender::MALE(), $this->object->origin());
    }

    public function test___get()
    {
        $this->assertNull($this->null->nothing->origin());
        $this->assertNull($this->int->nothing->origin());

        $this->assertNull($this->object->nothing->origin());
        $this->assertSame(1, $this->object->value->origin());
        $this->assertSame('Male', $this->object->label->origin());

        $this->assertNull($this->map->nothing->origin());
        $this->assertSame('FOO', $this->map->foo->origin());
        $this->assertSame([
            'child' => [
                'bar' => 'BAR',
            ],
        ], $this->map->parent->origin());
        $this->assertSame('BAR', $this->map->parent->child->bar->origin());
        $this->assertNull($this->map->parent->nothing->bar->origin());
    }

    public function test___call()
    {
        $this->assertNull($this->null->nothing()->origin());
        $this->assertSame(123, $this->int->nothing()->origin());

        $this->assertSame(Gender::MALE(), $this->object->nothing()->origin());
        $this->assertEquals(
            DateTime::valueOf('2002/02/03 04:05:06'),
            $this->datetime_o->addYear(1)->origin()
        );
        $this->assertEquals(
            '2001/02/03 04:05:06',
            $this->datetime_s->addYear(1)->origin()
        );
        $this->assertEquals(
            DateTime::valueOf('2002/02/03 04:05:06'),
            $this->datetime_s->convert(DateTime::class)->addYear(1)->origin()
        );

        $this->assertSame(0, $this->destructive->count->origin());
        $this->assertSame(1, $this->destructive->add_void()->count->origin());
        $this->assertSame(3, $this->destructive->add_void()->add_void()->count->origin());
        $this->assertSame(4, $this->destructive->add_bool()->count->origin());
        $this->assertSame(6, $this->destructive->add_bool()->add_bool()->count->origin());
        $this->assertSame(7, $this->destructive->add_nohint_void()->count->origin());
        $this->assertSame(9, $this->destructive->add_nohint_void()->add_nohint_void()->count->origin());
        $this->assertSame(10, $this->destructive->add_nohint_bool()->count->origin());
        $this->assertSame(12, $this->destructive->add_nohint_bool()->add_nohint_bool()->count->origin());
        $this->assertSame(13, $this->destructive->add_nohint_self()->count->origin());
        $this->assertSame(15, $this->destructive->add_nohint_self()->add_nohint_self()->count->origin());
        $this->assertSame(15, $this->destructive->add_nohint_self(0)->count->origin());
        $this->assertSame(15, $this->destructive->add_void(0)->count->origin());
        $this->assertSame(null, $this->destructive->add_nohint_void(0)->count->origin());
        $this->assertSame(true, $this->destructive->add_nohint_bool(0));
    }

    public function test___set()
    {
        $this->assertSame(0, $this->destructive->count->origin());
        $this->destructive->count = 12;
        $this->assertSame(12, $this->destructive->count->origin());

        $this->assertSame('FOO', $this->map->foo->origin());
        $this->map->foo = 'foo';
        $this->assertSame('foo', $this->map->foo->origin());
        $this->assertSame('BAR', $this->map->parent->child->bar->origin());
        $this->map->parent->child->bar = 'bar'; // can not cahnge the map origin
        $this->assertSame('BAR', $this->map->parent->child->bar->origin());
        $this->map->parent->qux->bar = 'qux'; // can not cahnge the map origin
        $this->assertSame(null, $this->map->parent->qux->bar->origin());
        $this->map->qux = 'qux';
        $this->assertSame('qux', $this->map->qux->origin());

        $this->assertSame(null, $this->map->nothing->origin());
        $this->map->nothing = 12;
        $this->assertSame(12, $this->map->nothing->origin());
    }

    public function test_offsetSet()
    {
        $this->assertSame(null, $this->null->origin());
        $this->null[] = 4;
        $this->assertSame(null, $this->null->origin());
        $this->null[0] = 'a';
        $this->assertSame(null, $this->null->origin());

        $this->assertSame(123, $this->int->origin());
        $this->int[] = 4;
        $this->assertSame(123, $this->int->origin());
        $this->int[0] = 'a';
        $this->assertSame(123, $this->int->origin());

        $this->assertSame([1, 2, 3], $this->array->origin());
        $this->array[] = 4;
        $this->assertSame([1, 2, 3, 4], $this->array->origin());
        $this->array[0] = 'a';
        $this->assertSame(['a', 2, 3, 4], $this->array->origin());

        $this->assertSame('FOO', $this->map['foo']->origin());
        $this->map['foo'] = 'foo';
        $this->assertSame('foo', $this->map['foo']->origin());
        $this->assertSame('BAR', $this->map['parent']['child']['bar']->origin());
        $this->assertSame('BAR', $this->map['parent.child.bar']->origin());
        $this->map['parent.child.bar'] = 'Bar';
        $this->assertSame('Bar', $this->map['parent.child.bar']->origin());
        $this->map['parent']['child']['bar'] = 'bar'; // can not change the map origin
        $this->assertSame('Bar', $this->map['parent']['child']['bar']->origin());

        $this->assertSame(0, $this->destructive['count']->origin());
        $this->destructive['count'] = 12;
        $this->assertSame(12, $this->destructive['count']->origin());
    }

    public function test_offsetExists()
    {
        $this->assertFalse(isset($this->null[0]));
        $this->assertFalse(isset($this->int[0]));

        $this->assertTrue(isset($this->array[0]));
        $this->assertFalse(isset($this->array[4]));

        $this->assertTrue(isset($this->map['foo']));
        $this->assertTrue(isset($this->map['parent.child.bar']));
        $this->assertTrue(isset($this->map['parent']['child']['bar']));

        $this->assertFalse(isset($this->map['nothing']));
        $this->assertFalse(isset($this->map['parent.nothing.bar']));
        $this->assertFalse(isset($this->map['parent']['nothing']['bar']));
    }

    public function test_offsetUnset()
    {
        $this->assertFalse(isset($this->null[0]));
        unset($this->null[0]);
        $this->assertFalse(isset($this->null[0]));

        $this->assertFalse(isset($this->int[0]));
        unset($this->int[0]);
        $this->assertFalse(isset($this->int[0]));

        $this->assertTrue(isset($this->array[0]));
        unset($this->array[0]);
        $this->assertFalse(isset($this->array[0]));

        $this->assertTrue(isset($this->map['foo']));
        unset($this->map['foo']);
        $this->assertFalse(isset($this->map['foo']));

        $this->assertTrue(isset($this->map['parent']['child']['bar']));
        unset($this->map['parent']['child']['bar']); // can not unset map origin
        $this->assertTrue(isset($this->map['parent']['child']['bar']));

        $this->assertTrue(isset($this->map['parent.child.bar']));
        unset($this->map['parent.child.bar']);
        $this->assertFalse(isset($this->map['parent.child.bar']));
    }

    public function test_offsetGet()
    {
        $this->assertSame(null, $this->null[0]->origin());
        $this->assertSame(1, $this->array[0]->origin());
        $this->assertSame('FOO', $this->map['foo']->origin());
        $this->assertSame('BAR', $this->map['parent.child.bar']->origin());
        $this->assertSame('BAR', $this->map['parent']['child']['bar']->origin());
        $this->assertSame(null, $this->map['parent']['nothing']['bar']->origin());
        $this->assertSame(null, $this->map['parent.nothing.bar']->origin());
    }

    public function test_count()
    {
        $this->assertSame(0, count($this->null));
        $this->assertSame(1, count($this->int));
        $this->assertSame(3, count($this->array));
        $this->assertSame(1, count($this->object));
    }

    public function test_getIterator()
    {
        foreach ($this->null as $key => $value) {
            fail('Never execute');
        }

        $expects = [123];
        $count   = 0;
        foreach ($this->int as $key => $value) {
            $this->assertInstanceOf(StreamAccessor::class, $value);
            $this->assertSame($expects[$key], $value->origin());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = ['Hello Rebet'];
        $count   = 0;
        foreach ($this->string as $key => $value) {
            $this->assertInstanceOf(StreamAccessor::class, $value);
            $this->assertSame($expects[$key], $value->origin());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = ['Hello', 'Rebet'];
        $count   = 0;
        foreach ($this->string->split(' ') as $key => $value) {
            $this->assertInstanceOf(StreamAccessor::class, $value);
            $this->assertSame($expects[$key], $value->origin());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = [1, 2, 3];
        $count   = 0;
        foreach ($this->array as $key => $value) {
            $this->assertInstanceOf(StreamAccessor::class, $value);
            $this->assertSame($expects[$key], $value->origin());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = ['value' => 1, 'label' => 'Male', 'name' => 'MALE'];
        $count   = 0;
        foreach ($this->object as $key => $value) {
            $this->assertInstanceOf(StreamAccessor::class, $value);
            $this->assertSame($expects[$key], $value->origin());
            $count++;
        }
        $this->assertSame(count($expects), $count);
    }

    public function test___toString()
    {
        $this->assertSame('', "{$this->null}");
        $this->assertSame('123', "{$this->int}");
        $this->assertSame('Hello Rebet', "{$this->string}");
        $this->assertSame('[1,2,3]', "{$this->array}");
        $this->assertSame('男性', "{$this->object}");
        $this->assertSame('2001-02-03 04:05:06', "{$this->datetime_o}");
        $this->assertSame('{"foo":"FOO","parent":{"child":{"bar":"BAR"}},"number":123,"gender":1}', "{$this->map}");
    }

    public function test___jsonSerialize()
    {
        $this->assertSame(null, $this->null->jsonSerialize());
        $this->assertSame(123, $this->int->jsonSerialize());
        $this->assertSame('Hello Rebet', $this->string->jsonSerialize());
        $this->assertSame([1, 2, 3], $this->array->jsonSerialize());
        $this->assertSame(1, $this->object->jsonSerialize());
        $this->assertSame('2001-02-03 04:05:06', $this->datetime_o->jsonSerialize());
        $this->assertSame(['foo' => 'FOO', 'parent' => ['child' => ['bar' => 'BAR']], 'number' => 123, 'gender' => 1], $this->map->jsonSerialize());
    }

    public function test_filters()
    {
        // convert
        $this->assertNull($this->null->_('convert', 'string')->origin());
        $this->assertSame('123', $this->int->_('convert', 'string')->origin());

        $this->assertSame('123', $this->int->convert('string')->origin());

        // escape:html
        $this->assertNull($this->null->_('escape')->origin());
        $this->assertSame('123', $this->int->_('escape')->origin());
        $this->assertSame('Hello Rebet', $this->string->_('escape')->origin());
        $this->assertSame('&lt;h1&gt;Hello Rebet&lt;/h1&gt;', $this->html->_('escape')->origin());
        $this->assertSame('男性', $this->object->_('escape')->origin());

        $this->assertSame('&lt;h1&gt;Hello Rebet&lt;/h1&gt;', $this->html->escape()->origin());

        // escape:url
        $this->assertNull($this->null->_('escape', 'url')->origin());
        $this->assertSame('Hello+Rebet', $this->string->_('escape', 'url')->origin());
        $this->assertSame('%3Ch1%3EHello+Rebet%3C%2Fh1%3E', $this->html->_('escape', 'url')->origin());

        $this->assertSame('%3Ch1%3EHello+Rebet%3C%2Fh1%3E', $this->html->escape('url')->origin());

        // nl2br
        $this->assertNull($this->null->_('nl2br')->origin());
        $this->assertSame('Hello Rebet', $this->string->_('nl2br')->origin());
        $this->assertSame("Hello<br />\nRebet", $this->text->_('nl2br')->origin());

        $this->assertSame("Hello<br />\nRebet", $this->text->nl2br()->origin());

        // datetime
        $this->assertNull($this->null->_('datetime', 'Ymd')->origin());
        $this->assertSame('20010203', $this->datetime_o->_('datetime', 'Ymd')->origin());
        $this->assertSame('20010203', $this->datetime_s->_('datetime', 'Ymd')->origin());

        $this->assertSame('20010203', $this->datetime_s->datetime('Ymd')->origin());

        // number
        $this->assertNull($this->null->_('number')->origin());
        $this->assertSame('123', $this->int->_('number')->origin());
        $this->assertSame('1,235', $this->float->_('number')->origin());
        $this->assertSame('1,234.6', $this->float->_('number', 1)->origin());
        $this->assertSame('1,234.57', $this->float->_('number', 2)->origin());
        $this->assertSame('1,234.568', $this->float->_('number', 3)->origin());
        $this->assertSame('1,234.5678', $this->float->_('number', 4)->origin());
        $this->assertSame('1,234.56780', $this->float->_('number', 5)->origin());
        $this->assertSame('1234.57', $this->float->_('number', 2, '.', '')->origin());
        $this->assertSame('1 234,57', $this->float->_('number', 2, ',', ' ')->origin());

        // text
        $this->assertNull($this->null->_('text', '%s')->origin());
        $this->assertNull($this->null->_('text', '[%s]')->origin());

        $this->assertSame('[123]', $this->int->_('text', '[%s]')->origin());
        $this->assertSame('[000123]', $this->int->_('text', '[%06d]')->origin());
        $this->assertSame('[123.00]', $this->int->_('text', '[%01.2f]')->origin());
        $this->assertSame('[1234.57]', $this->float->_('text', '[%01.2f]')->origin());

        $this->assertSame('[1234.57]', $this->float->text('[%01.2f]')->origin());

        // default
        $this->assertSame('(null)', $this->null->_('default', '(null)')->origin());
        $this->assertSame(123, $this->int->_('default', '(null)')->origin());
        $this->assertSame('(null)', $this->int->nothing->_('default', '(null)')->origin());

        $this->assertSame('(null)', $this->null->default('(null)')->origin());

        // split
        $this->assertNull($this->null->_('split', ' ')->origin());
        $this->assertSame(['Hello', 'Rebet'], $this->string->_('split', ' ')->origin());

        $this->assertSame(['Hello', 'Rebet'], $this->string->split(' ')->origin());

        // join
        $this->assertNull($this->null->_('join', ',')->origin());
        $this->assertSame('1, 2, 3', $this->array->_('join', ', ')->origin());

        $this->assertSame('1, 2, 3', $this->array->join(', ')->origin());
        $this->assertSame('[1, 2, 3]', $this->array->join(', ')->text('[%s]')->origin());

        // replace
        $this->assertNull($this->null->_('replace', '/Hello/', 'Good by')->origin());
        $this->assertSame('Good by Rebet', $this->string->_('replace', '/Hello/', 'Good by')->origin());

        $this->assertSame('Good by Rebet', $this->string->replace('/Hello/', 'Good by')->origin());

        // cut
        $this->assertNull($this->null->_('cut', 4)->origin());
        $this->assertSame('Hell...', $this->string->_('cut', 7)->origin());
        $this->assertSame('Hello R', $this->string->_('cut', 7, '')->origin());
        $this->assertSame('Hell《略》', $this->string->_('cut', 7, '《略》')->origin());

        $this->assertSame('Hell...', $this->string->cut(7)->origin());

        // lower
        $this->assertNull($this->null->_('lower')->origin());
        $this->assertSame('hello rebet', $this->string->_('lower')->origin());

        $this->assertSame('hello rebet', $this->string->lower()->origin());

        // upper
        $this->assertNull($this->null->_('upper')->origin());
        $this->assertSame('HELLO REBET', $this->string->_('upper')->origin());

        $this->assertSame('HELLO REBET', $this->string->upper()->origin());

        // floor
        // $this->assertNull($this->null->_('floor')->origin());
        // $this->assertSame('1234', $this->float->_('floor')->origin());
        // $this->assertSame('1234.56', $this->float->_('floor', 2)->origin());
        // $this->assertSame('1200', $this->float->_('floor', -2)->origin());

        // $this->assertSame('1234', $this->string->floor()->origin());
        // $this->assertSame('1234.56', $this->string->floor(2)->origin());
    }
}

class StreamAccessorTest_DestructiveMock
{
    public $count = 0;

    public function add_void(int $i = 1) : void
    {
        $this->count += $i;
    }

    public function add_bool(int $i = 1) : bool
    {
        $this->count += $i;
        return true;
    }

    public function add_nohint_void(int $i = 1)
    {
        $this->count += $i;
    }

    public function add_nohint_bool(int $i = 1)
    {
        $this->count += $i;
        return true;
    }

    public function add_nohint_self(int $i = 1)
    {
        $this->count += $i;
        return $this;
    }
}
