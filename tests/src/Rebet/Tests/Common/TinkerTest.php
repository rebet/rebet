<?php
namespace Rebet\Tests\Common;

use InvalidArgumentException;
use Rebet\Common\Arrays;
use Rebet\Common\Decimal;
use Rebet\Common\Exception\LogicException;
use Rebet\Common\Tinker;
use Rebet\DateTime\DateTime;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\RebetTestCase;

class TinkerTest extends RebetTestCase
{
    private $null;
    private $int;
    private $float;
    private $string;
    private $text;
    private $html;
    private $json;
    private $enum;
    private $enums;
    private $datetime_o;
    private $datetime_s;
    private $array;
    private $map;
    private $rs;
    private $callable;
    private $destructive;

    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2001/02/03 04:05:06');
        $this->null       = Tinker::with(null);
        $this->int        = Tinker::with(123);
        $this->float      = Tinker::with(1234.5678);
        $this->string     = Tinker::with("Hello Rebet");
        $this->text       = Tinker::with("Hello\nRebet");
        $this->html       = Tinker::with("<h1>Hello Rebet</h1>");
        $this->json       = Tinker::with("[1 ,2, 3]");
        $this->enum       = Tinker::with(Gender::MALE());
        $this->enums      = Tinker::with(Gender::lists());
        $this->datetime_o = Tinker::with(DateTime::now());
        $this->datetime_s = Tinker::with('2001/02/03 04:05:06');
        $this->array      = Tinker::with([1, 2, 3]);
        $this->map        = Tinker::with([
            'foo'    => 'FOO',
            'parent' => [
                'child' => [
                    'bar' => 'BAR',
                ],
            ],
            'number'  => 123,
            'gender'  => Gender::MALE(),
            'boolean' => true,
        ]);
        $this->rs          = Tinker::with([
            new TinkerTest_User(1, 'Foo', 'First', 'foo@hoge.com', Gender::MALE(), new DateTime('1976-08-12')),
            new TinkerTest_User(2, 'Bar', 'Second', 'bar@moge.net', Gender::FEMALE(), new DateTime('1993-11-27')),
            new TinkerTest_User(3, 'Baz', 'Third', 'baz@piyo.co.jp', Gender::MALE(), new DateTime('2000-02-05')),
            new TinkerTest_User(4, 'Qux', 'Fourth', 'qux@hoge.com', Gender::FEMALE(), new DateTime('1968-07-18')),
            new TinkerTest_User(5, 'Quxx', 'Fifth', 'quxx@moge.net', Gender::FEMALE(), new DateTime('1983-04-21')),
        ]);
        $this->callable    = Tinker::with(function (string $value) { return "Hello {$value}"; });
        $this->destructive = Tinker::with(new TinkerTest_DestructiveMock());
        $this->safty       = Tinker::with("Hello Rebet", true);
    }

    public function test_of()
    {
        $this->assertInstanceOf(Tinker::class, Tinker::with(123));
    }

    public function test_promise()
    {
        $source = null;
        $value  = Tinker::promise(function () use (&$source) { return $source; });
        $this->assertInstanceOf(Tinker::class, $value);

        $source = 1;
        $this->assertSame(1, $value->return());

        $source = 2;
        $this->assertSame(1, $value->return());
    }

    public function test_addFilter()
    {
        $this->assertSame("Hello Rebet", $this->string->wrap()->return());
        $this->assertSame("Hello Rebet", $this->safty->wrap()->return());
        Tinker::addFilter('wrap', function ($value) { return "({$value})"; });
        $this->assertSame("(Hello Rebet)", $this->string->wrap()->return());
        $this->assertSame("Hello Rebet", $this->safty->wrap()->return());

        $this->assertSame("HELLO REBET", $this->string->upper()->return());
        $this->assertSame("HELLO REBET", $this->safty->upper()->return());
        Tinker::addFilter('upper', function ($value) { return "Upper: $value"; });
        $this->assertSame("Upper: Hello Rebet", $this->string->upper()->return());
        $this->assertSame("HELLO REBET", $this->safty->upper()->return());
    }

    public function test_return()
    {
        $this->assertSame(123, $this->int->return());
        $this->assertSame("Hello Rebet", $this->string->return());
        $this->assertSame(Gender::MALE(), $this->enum->return());
    }

    public function test___get()
    {
        $this->assertNull($this->null->nothing->return());
        $this->assertNull($this->int->nothing->return());

        $this->assertNull($this->enum->nothing->return());
        $this->assertSame(1, $this->enum->value->return());
        $this->assertSame('Male', $this->enum->label->return());

        $this->assertNull($this->map->nothing->return());
        $this->assertSame('FOO', $this->map->foo->return());
        $this->assertSame([
            'child' => [
                'bar' => 'BAR',
            ],
        ], $this->map->parent->return());
        $this->assertSame('BAR', $this->map->parent->child->bar->return());
        $this->assertNull($this->map->parent->nothing->bar->return());

        $this->assertTrue($this->map->boolean);
    }

    public function test___call()
    {
        $this->assertNull($this->null->nothing()->return());
        $this->assertSame(123, $this->int->nothing()->return());

        $this->assertSame(Gender::MALE(), $this->enum->nothing()->return());
        $this->assertEquals(
            DateTime::valueOf('2002/02/03 04:05:06'),
            $this->datetime_o->addYear(1)->return()
        );
        $this->assertEquals(
            '2001/02/03 04:05:06',
            $this->datetime_s->addYear(1)->return()
        );
        $this->assertEquals(
            DateTime::valueOf('2002/02/03 04:05:06'),
            $this->datetime_s->convert(DateTime::class)->addYear(1)->return()
        );

        $this->assertSame(0, $this->destructive->count->return());
        $this->assertSame(1, $this->destructive->add_void()->count->return());
        $this->assertSame(3, $this->destructive->add_void()->add_void()->count->return());
        $this->assertSame(4, $this->destructive->add_bool()->count->return());
        $this->assertSame(6, $this->destructive->add_bool()->add_bool()->count->return());
        $this->assertSame(7, $this->destructive->add_nohint_void()->count->return());
        $this->assertSame(9, $this->destructive->add_nohint_void()->add_nohint_void()->count->return());
        $this->assertSame(10, $this->destructive->add_nohint_bool()->count->return());
        $this->assertSame(12, $this->destructive->add_nohint_bool()->add_nohint_bool()->count->return());
        $this->assertSame(13, $this->destructive->add_nohint_self()->count->return());
        $this->assertSame(15, $this->destructive->add_nohint_self()->add_nohint_self()->count->return());
        $this->assertSame(15, $this->destructive->add_nohint_self(0)->count->return());
        $this->assertSame(15, $this->destructive->add_void(0)->count->return());
        $this->assertSame(null, $this->destructive->add_nohint_void(0)->count->return());
        $this->assertSame(true, $this->destructive->add_nohint_bool(0));
    }

    public function test___set()
    {
        $this->assertSame(0, $this->destructive->count->return());
        $this->destructive->count = 12;
        $this->assertSame(12, $this->destructive->count->return());

        $this->assertSame('FOO', $this->map->foo->return());
        $this->map->foo = 'foo';
        $this->assertSame('foo', $this->map->foo->return());
        $this->assertSame('BAR', $this->map->parent->child->bar->return());
        $this->map->parent->child->bar = 'bar'; // can not cahnge the map origin
        $this->assertSame('BAR', $this->map->parent->child->bar->return());
        $this->map->parent->qux->bar = 'qux'; // can not cahnge the map origin
        $this->assertSame(null, $this->map->parent->qux->bar->return());
        $this->map->qux = 'qux';
        $this->assertSame('qux', $this->map->qux->return());

        $this->assertSame(null, $this->map->nothing->return());
        $this->map->nothing = 12;
        $this->assertSame(12, $this->map->nothing->return());
    }

    public function test_offsetSet()
    {
        $this->assertSame(null, $this->null->return());
        $this->null[] = 4;
        $this->assertSame(null, $this->null->return());
        $this->null[0] = 'a';
        $this->assertSame(null, $this->null->return());

        $this->assertSame(123, $this->int->return());
        $this->int[] = 4;
        $this->assertSame(123, $this->int->return());
        $this->int[0] = 'a';
        $this->assertSame(123, $this->int->return());

        $this->assertSame([1, 2, 3], $this->array->return());
        $this->array[] = 4;
        $this->assertSame([1, 2, 3, 4], $this->array->return());
        $this->array[0] = 'a';
        $this->assertSame(['a', 2, 3, 4], $this->array->return());

        $this->assertSame('FOO', $this->map['foo']->return());
        $this->map['foo'] = 'foo';
        $this->assertSame('foo', $this->map['foo']->return());
        $this->assertSame('BAR', $this->map['parent']['child']['bar']->return());
        $this->assertSame('BAR', $this->map['parent.child.bar']->return());
        $this->map['parent.child.bar'] = 'Bar';
        $this->assertSame('Bar', $this->map['parent.child.bar']->return());
        $this->map['parent']['child']['bar'] = 'bar'; // can not change the map origin
        $this->assertSame('Bar', $this->map['parent']['child']['bar']->return());

        $this->assertSame(0, $this->destructive['count']->return());
        $this->destructive['count'] = 12;
        $this->assertSame(12, $this->destructive['count']->return());
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
        $this->assertSame(null, $this->null[0]->return());
        $this->assertSame(1, $this->array[0]->return());
        $this->assertSame('FOO', $this->map['foo']->return());
        $this->assertSame('BAR', $this->map['parent.child.bar']->return());
        $this->assertSame('BAR', $this->map['parent']['child']['bar']->return());
        $this->assertSame(null, $this->map['parent']['nothing']['bar']->return());
        $this->assertSame(null, $this->map['parent.nothing.bar']->return());
        $this->assertSame(true, $this->map['boolean']);
    }

    public function test_count()
    {
        $this->assertSame(0, count($this->null));
        $this->assertSame(1, count($this->int));
        $this->assertSame(3, count($this->array));
        $this->assertSame(1, count($this->enum));
    }

    public function test_getIterator()
    {
        foreach ($this->null as $key => $value) {
            fail('Never execute');
        }

        $expects = [123];
        $count   = 0;
        foreach ($this->int as $key => $value) {
            $this->assertInstanceOf(Tinker::class, $value);
            $this->assertSame($expects[$key], $value->return());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = ['Hello Rebet'];
        $count   = 0;
        foreach ($this->string as $key => $value) {
            $this->assertInstanceOf(Tinker::class, $value);
            $this->assertSame($expects[$key], $value->return());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = ['Hello', 'Rebet'];
        $count   = 0;
        foreach ($this->string->explode(' ') as $key => $value) {
            $this->assertInstanceOf(Tinker::class, $value);
            $this->assertSame($expects[$key], $value->return());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = [1, 2, 3];
        $count   = 0;
        foreach ($this->array as $key => $value) {
            $this->assertInstanceOf(Tinker::class, $value);
            $this->assertSame($expects[$key], $value->return());
            $count++;
        }
        $this->assertSame(count($expects), $count);

        $expects = ['value' => 1, 'label' => 'Male', 'name' => 'MALE'];
        $count   = 0;
        foreach ($this->enum as $key => $value) {
            $this->assertInstanceOf(Tinker::class, $value);
            $this->assertSame($expects[$key], $value->return());
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
        $this->assertSame('男性', "{$this->enum}");
        $this->assertSame('2001-02-03 04:05:06', "{$this->datetime_o}");
        $this->assertSame('{"foo":"FOO","parent":{"child":{"bar":"BAR"}},"number":123,"gender":1,"boolean":true}', "{$this->map}");
    }

    public function test___jsonSerialize()
    {
        $this->assertSame(null, $this->null->jsonSerialize());
        $this->assertSame(123, $this->int->jsonSerialize());
        $this->assertSame('Hello Rebet', $this->string->jsonSerialize());
        $this->assertSame([1, 2, 3], $this->array->jsonSerialize());
        $this->assertSame(1, $this->enum->jsonSerialize());
        $this->assertSame('2001-02-03 04:05:06', $this->datetime_o->jsonSerialize());
        $this->assertSame(['foo' => 'FOO', 'parent' => ['child' => ['bar' => 'BAR']], 'number' => 123, 'gender' => 1, 'boolean' => true], $this->map->jsonSerialize());
    }

    public function test_filters()
    {
        // Call filter using method
        $this->assertNull($this->null->_('convert', 'string')->return());
        $this->assertSame('123', $this->int->_('convert', 'string')->return());

        // Reflector::convert
        $this->assertNull($this->null->convert('string')->return());
        $this->assertSame('123', $this->int->convert('string')->return());
        $this->assertSame(true, Tinker::with(1)->convert('bool'));

        // Utils::isBlank
        $this->assertTrue($this->null->isBlank());
        $this->assertFalse($this->int->isBlank());

        // Utils::bvl
        $this->assertSame('(blank)', $this->null->bvl('(blank)')->return());
        $this->assertSame(123, $this->int->bvl('(blank)')->return());
        $this->assertSame(true, Tinker::with(true)->bvl('(blank)'));

        // Utils::isEmpty
        $this->assertTrue($this->null->isEmpty());
        $this->assertFalse($this->int->isEmpty());

        // Utils::evl
        $this->assertSame('(empty)', $this->null->evl('(empty)')->return());
        $this->assertSame(123, $this->int->evl('(empty)')->return());
        $this->assertSame(true, Tinker::with(true)->evl('(empty)'));

        // Strings::lcut
        $this->assertNull($this->null->lcut(4)->return());
        $this->assertSame('ebet', $this->string->lcut(7)->return());

        // Strings::rcut
        $this->assertNull($this->null->rcut(4)->return());
        $this->assertSame('Hell', $this->string->rcut(7)->return());

        // Strings::clip
        $this->assertNull($this->null->clip(4)->return());
        $this->assertSame('Hell...', $this->string->clip(7)->return());
        $this->assertSame('Hello R', $this->string->clip(7, '')->return());
        $this->assertSame('Hell《略》', $this->string->clip(7, '《略》')->return());

        // Strings::indent
        $this->assertNull($this->null->indent('> ')->return());
        $this->assertSame("> Hello\n> Rebet", $this->text->indent('> ')->return());

        // Strings::ltrim
        $this->assertNull($this->null->ltrim('He')->return());
        $this->assertSame("llo Rebet", $this->string->ltrim('He')->return());

        // Strings::rtrim
        $this->assertNull($this->null->rtrim('et')->return());
        $this->assertSame("Hello Reb", $this->string->rtrim('et')->return());

        // Strings::trim
        $this->assertNull($this->null->trim()->return());
        $this->assertSame("　trim　", Tinker::with(' 　trim　 ')->trim()->return());

        // Strings::mbtrim
        $this->assertNull($this->null->mbtrim()->return());
        $this->assertSame("trim", Tinker::with(' 　trim　 ')->mbtrim()->return());

        // Strings::startsWith
        $this->assertSame(true, $this->string->startsWith('Hello'));
        $this->assertSame(false, $this->string->startsWith('Rebet'));

        // Strings::endsWith
        $this->assertSame(false, $this->string->endsWith('Hello'));
        $this->assertSame(true, $this->string->endsWith('Rebet'));

        // Strings::contains
        $this->assertSame(true, $this->string->contains('o R'));
        $this->assertSame(false, $this->string->contains('foo'));

        // Strings::match
        $this->assertSame(true, $this->string->match('/^He/'));
        $this->assertSame(false, $this->string->match('/foo/'));

        // Strings::wildmatch
        $this->assertSame(true, $this->string->wildmatch('He*'));
        $this->assertSame(false, $this->string->wildmatch('Foo*'));

        // Strings::split
        $this->assertSame(['Hello', 'Rebet', null], $this->string->split(' ', 3)->return());


        // Arrays::pluck
        $this->assertSame(['Foo', 'Bar', 'Baz', 'Qux', 'Quxx'], $this->rs->pluck('first_name')->return());

        // Arrays::override
        $this->assertSame(
            ['foo' => 'foo', 'parent' => null, 'number' => 123, 'gender' => Gender::MALE(), 'boolean' => true],
            $this->map->override(['foo' => 'foo', 'parent!' => null])->return()
        );

        // Arrays::duplicate
        $this->assertSame([2, 3], Tinker::with([1, 2, 2, 3, 4, 3])->duplicate()->return());

        // Arrays::crossJoin
        $this->assertSame([[1, 'a'], [1, 'b'], [2, 'a'], [2, 'b']], Tinker::with([1, 2])->crossJoin(['a', 'b'])->return());

        // Arrays::only
        $this->assertSame(['foo' => 'FOO', 'number' => 123], $this->map->only(['foo', 'number'])->return());

        // Arrays::except
        $this->assertSame(['foo' => 'FOO', 'number' => 123, 'boolean' => true], $this->map->except(['parent', 'gender'])->return());

        // Arrays::where
        $this->assertSame(['foo' => 'FOO', 'number' => 123, 'boolean' => true], $this->map->where(function ($v, $k) { return is_scalar($v); })->return());

        // Arrays::compact
        $this->assertSame([0 => 1, 2 => 3], Tinker::with([1, null, 3])->compact()->return());

        // Arrays::unique
        $this->assertSame([0 => 1, 2 => 2, 3 => 3], Tinker::with([1, 1, 2, 3, '3'])->unique()->return());

        // Arrays::first
        $this->assertSame(1, Tinker::with([1, 2, 3])->first()->return());
        $this->assertSame(true, Tinker::with([true, 1, 2, 3])->first());

        // Arrays::last
        $this->assertSame(3, Tinker::with([1, 2, 3])->last()->return());
        $this->assertSame(false, Tinker::with([1, 2, 3, false])->last());

        // Arrays::flatten
        $this->assertSame([1, 'a', 'b', 3], Tinker::with([1, ['a', 'b'], 3])->flatten()->return());

        // Arrays::prepend
        $this->assertSame(['a', 1, 2, 3], Tinker::with([1, 2, 3])->prepend('a')->return());

        // Arrays::shuffle
        for ($i = 0 ; $i < 10 ; $i++) {
            if ([1, 2, 3, 4] != Tinker::with([1, 2, 3, 4])->shuffle()->return()) {
                break;
            }
        }
        if ($i === 10) {
            $this->fail("Tinker::shuffle failed.");
        } else {
            $this->assertTrue(true);
        }

        // Arrays::map
        $this->assertSame([2, 4, 6], Tinker::with([1, 2, 3])->map(function ($v, $k) { return $v * 2; })->return());

        // Arrays::reduce
        $this->assertSame(6, Tinker::with([1, 2, 3])->reduce(function ($c, $i) { return $c + $i; }, 0)->return());
        $this->assertSame(true, Tinker::with([1, 2, 3])->reduce(function ($c, $i) { return $c && ($i < 10); }, true));

        // Arrays::diff
        $this->assertSame([1 => 2], Tinker::with([1, 2, 3])->diff([1, 3, 4])->return());

        // Arrays::intersect
        $this->assertSame([0 => 1, 2 => 3], Tinker::with([1, 2, 3])->intersect([1, 3, 4])->return());

        // Arrays::every
        $this->assertSame(false, Tinker::with([1, 2, 3])->every(function ($v) { return $v % 2 === 1; }));

        // Arrays::groupBy
        $this->assertSame([
            1 => [
                ['rating' => 1, 'url' => 'a'],
                ['rating' => 1, 'url' => 'b'],
            ],
            2 => [
                ['rating' => 2, 'url' => 'b'],
            ],
        ], Tinker::with([
            ['rating' => 1, 'url' => 'a'],
            ['rating' => 1, 'url' => 'b'],
            ['rating' => 2, 'url' => 'b'],
        ])->groupBy('rating')->return());

        // Arrays::union
        $this->assertSame(['name' => 'Hello', 'id' => 1], Tinker::with(['name' => 'Hello'])->union(['name' => 'World', 'id' => 1])->return());

        // Arrays::min
        $this->assertSame(-2, Tinker::with([1, 2, 5, -2, 4])->min()->return());
        $this->assertSame(false, Tinker::with([false, true])->min());

        // Arrays::max
        $this->assertSame(5, Tinker::with([1, 2, 5, -2, 4])->max()->return());
        $this->assertSame(true, Tinker::with([false, true])->max());

        // Arrays::sort
        $this->assertSame([1 => 1, 3 => 2, 2 => 3, 0 => 4], Tinker::with([4, 1, 3, 2])->sort()->return());

        // Arrays::sortBy
        $this->assertSame(
            [1 => ['age' => '8'], 2 => ['age' => '14'], 0 => ['age' => '23']],
            Tinker::with([['age' => '23'], ['age' => '8'], ['age' => '14']])->sortBy('age')->return()
        );

        // Arrays::sortKeys
        $this->assertSame(
            ['a' => 'A', 'b' => 'B', 'c' => 'C'],
            Tinker::with(['c' => 'C', 'a' => 'A', 'b' => 'B'])->sortKeys()->return()
        );

        // Arrays::sum
        $this->assertEquals("55", Tinker::with(range(1, 10))->sum()->value()->return());

        // Arrays::avg
        $this->assertEquals("5.5", Tinker::with(range(1, 10))->avg()->value()->return());

        // Arrays::median
        $this->assertEquals("5.5", Tinker::with(range(1, 10))->median()->value()->return());

        // Arrays::mode
        $this->assertEquals([1, 4], Tinker::with([1, 2, 3, 4, 4, 5, 1])->mode()->return());

        // Arrays::implode
        $this->assertEquals('1, 2, 3', Tinker::with([1, 2, 3])->implode()->return());


        // Tinker.filter.customs.nvl
        $this->assertSame('(null)', $this->null->nvl('(null)')->return());
        $this->assertSame(123, $this->int->nvl('(null)')->return());
        $this->assertSame('(null)', $this->int->nothing->nvl('(null)')->return());

        // Tinker.filter.customs.default
        $this->assertSame('(null)', $this->null->default('(null)')->return());
        $this->assertSame(123, $this->int->default('(null)')->return());
        $this->assertSame('(null)', $this->int->nothing->default('(null)')->return());

        // Tinker.filter.customs.escape:html
        $this->assertNull($this->null->escape()->return());
        $this->assertSame('123', $this->int->escape()->return());
        $this->assertSame('Hello Rebet', $this->string->escape()->return());
        $this->assertSame('&lt;h1&gt;Hello Rebet&lt;/h1&gt;', $this->html->escape()->return());
        $this->assertSame('男性', $this->enum->escape()->return());

        // Tinker.filter.customs.escape:url
        $this->assertNull($this->null->escape('url')->return());
        $this->assertSame('Hello+Rebet', $this->string->escape('url')->return());
        $this->assertSame('%3Ch1%3EHello+Rebet%3C%2Fh1%3E', $this->html->escape('url')->return());

        // Tinker.filter.customs.nl2br
        $this->assertNull($this->null->nl2br()->return());
        $this->assertSame('Hello Rebet', $this->string->nl2br()->return());
        $this->assertSame("Hello<br />\nRebet", $this->text->nl2br()->return());

        // Tinker.filter.customs.datetimef
        $this->assertNull($this->null->datetimef('Ymd')->return());
        $this->assertSame('20010203', $this->datetime_o->datetimef('Ymd')->return());
        $this->assertSame('20010203', $this->datetime_s->datetimef('Ymd')->return());

        // Tinker.filter.customs.numberf
        $this->assertNull($this->null->numberf()->return());
        $this->assertSame('123', $this->int->numberf()->return());
        $this->assertSame('1,235', $this->float->numberf()->return());
        $this->assertSame('1,235', $this->float->numberf(0)->return());
        $this->assertSame('1,234.6', $this->float->numberf(1)->return());
        $this->assertSame('1,234.57', $this->float->numberf(2)->return());
        $this->assertSame('1,234.568', $this->float->numberf(3)->return());
        $this->assertSame('1,234.5678', $this->float->numberf(4)->return());
        $this->assertSame('1,234.56780', $this->float->numberf(5)->return());
        $this->assertSame('1,234.5678', $this->float->numberf(5, true)->return());
        $this->assertSame('1234.57', $this->float->numberf(2, false, '.', '')->return());
        $this->assertSame('1 234,57', $this->float->numberf(2, false, ',', ' ')->return());

        // Tinker.filter.customs.stringf
        $this->assertNull($this->null->stringf('%s')->return());
        $this->assertNull($this->null->stringf('[%s]')->return());
        $this->assertSame('[123]', $this->int->stringf('[%s]')->return());
        $this->assertSame('[000123]', $this->int->stringf('[%06d]')->return());
        $this->assertSame('[123.00]', $this->int->stringf('[%01.2f]')->return());
        $this->assertSame('[1234.57]', $this->float->stringf('[%01.2f]')->return());

        // Tinker.filter.customs.explode
        $this->assertNull($this->null->explode(' ')->return());
        $this->assertSame(['Hello', 'Rebet'], $this->string->explode(' ')->return());

        // Tinker.filter.customs.replace
        $this->assertNull($this->null->replace('/Hello/', 'Good by')->return());
        $this->assertSame('Good by Rebet', $this->string->replace('/Hello/', 'Good by')->return());

        // Tinker.filter.customs.lower
        $this->assertNull($this->null->lower()->return());
        $this->assertSame('hello rebet', $this->string->lower()->return());

        // Tinker.filter.customs.upper
        $this->assertNull($this->null->upper()->return());
        $this->assertSame('HELLO REBET', $this->string->upper()->return());

        // Tinker.filter.customs.decimal
        $this->assertNull($this->null->decimal()->return());
        $this->assertEquals(Decimal::of(123), $this->int->decimal()->return());
        $this->assertEquals(Decimal::of("12.3"), Tinker::with("12.3")->decimal()->return());

        // Tinker.filter.customs.abs
        $this->assertNull($this->null->abs()->return());
        $this->assertEquals("123", Tinker::with(-123)->abs()->value()->return());

        // Tinker.filter.customs.eq
        $this->assertSame(false, $this->null->eq(null));
        $this->assertSame(false, $this->null->eq(1));
        $this->assertSame(true, $this->int->eq(123));
        $this->assertSame(true, $this->float->eq(1234.5678));
        $this->assertSame(false, $this->float->eq(1234.56789));
        $this->assertSame(true, $this->float->eq(1234.56789, 2));

        // Tinker.filter.customs.gt
        $this->assertSame(false, $this->null->gt(null));
        $this->assertSame(false, $this->null->gt(1));
        $this->assertSame(false, $this->int->gt(null));
        $this->assertSame(true, $this->int->gt(122));
        $this->assertSame(false, $this->int->gt(123));
        $this->assertSame(false, $this->int->gt(124));
        $this->assertSame(true, $this->float->gt(1234.56779));
        $this->assertSame(false, $this->float->gt(1234.5678));
        $this->assertSame(false, $this->float->gt(1234.56781));
        $this->assertSame(false, $this->float->gt(1234.56779, 2));
        $this->assertSame(false, $this->float->gt(1234.5678, 2));
        $this->assertSame(false, $this->float->gt(1234.56781, 2));

        // Tinker.filter.customs.gte
        $this->assertSame(false, $this->null->gte(null));
        $this->assertSame(false, $this->null->gte(1));
        $this->assertSame(false, $this->int->gte(null));
        $this->assertSame(true, $this->int->gte(122));
        $this->assertSame(true, $this->int->gte(123));
        $this->assertSame(false, $this->int->gte(124));
        $this->assertSame(true, $this->float->gte(1234.56779));
        $this->assertSame(true, $this->float->gte(1234.5678));
        $this->assertSame(false, $this->float->gte(1234.56781));
        $this->assertSame(true, $this->float->gte(1234.56779, 2));
        $this->assertSame(true, $this->float->gte(1234.5678, 2));
        $this->assertSame(true, $this->float->gte(1234.56781, 2));

        // Tinker.filter.customs.lt
        $this->assertSame(false, $this->null->lt(null));
        $this->assertSame(false, $this->null->lt(1));
        $this->assertSame(false, $this->int->lt(null));
        $this->assertSame(false, $this->int->lt(122));
        $this->assertSame(false, $this->int->lt(123));
        $this->assertSame(true, $this->int->lt(124));
        $this->assertSame(false, $this->float->lt(1234.56779));
        $this->assertSame(false, $this->float->lt(1234.5678));
        $this->assertSame(true, $this->float->lt(1234.56781));
        $this->assertSame(false, $this->float->lt(1234.56779, 2));
        $this->assertSame(false, $this->float->lt(1234.5678, 2));
        $this->assertSame(false, $this->float->lt(1234.56781, 2));

        // Tinker.filter.customs.lte
        $this->assertSame(false, $this->null->lte(null));
        $this->assertSame(false, $this->null->lte(1));
        $this->assertSame(false, $this->int->lte(null));
        $this->assertSame(false, $this->int->lte(122));
        $this->assertSame(true, $this->int->lte(123));
        $this->assertSame(true, $this->int->lte(124));
        $this->assertSame(false, $this->float->lte(1234.56779));
        $this->assertSame(true, $this->float->lte(1234.5678));
        $this->assertSame(true, $this->float->lte(1234.56781));
        $this->assertSame(true, $this->float->lte(1234.56779, 2));
        $this->assertSame(true, $this->float->lte(1234.5678, 2));
        $this->assertSame(true, $this->float->lte(1234.56781, 2));

        // Tinker.filter.customs.add
        $this->assertSame(null, $this->null->add(null)->value()->return());
        $this->assertSame(null, $this->null->add(1)->value()->return());
        $this->assertSame(null, $this->int->add(null)->value()->return());
        $this->assertSame('125', $this->int->add(2)->value()->return());

        // Tinker.filter.customs.sub
        $this->assertSame(null, $this->null->sub(null)->value()->return());
        $this->assertSame(null, $this->null->sub(1)->value()->return());
        $this->assertSame(null, $this->int->sub(null)->value()->return());
        $this->assertSame('121', $this->int->sub(2)->value()->return());

        // Tinker.filter.customs.mul
        $this->assertSame(null, $this->null->mul(null)->value()->return());
        $this->assertSame(null, $this->null->mul(1)->value()->return());
        $this->assertSame(null, $this->int->mul(null)->value()->return());
        $this->assertSame('246', $this->int->mul(2)->value()->return());

        // Tinker.filter.customs.div
        $this->assertSame(null, $this->null->div(null)->value()->return());
        $this->assertSame(null, $this->null->div(1)->value()->return());
        $this->assertSame(null, $this->int->div(null)->value()->return());
        $this->assertSame('61.5', $this->int->div(2)->value()->return());

        // Tinker.filter.customs.pow
        $this->assertSame(null, $this->null->pow(null)->value()->return());
        $this->assertSame(null, $this->null->pow(1)->value()->return());
        $this->assertSame(null, $this->int->pow(null)->value()->return());
        $this->assertSame('15129', $this->int->pow(2)->value()->return());

        // Tinker.filter.customs.sqrt
        $this->assertSame(null, $this->null->sqrt()->value()->return());
        $this->assertSame('3', Tinker::with(9)->sqrt()->value()->return());

        // Tinker.filter.customs.mod
        $this->assertSame(null, $this->null->mod(null)->value()->return());
        $this->assertSame(null, $this->null->mod(1)->value()->return());
        $this->assertSame(null, $this->int->mod(null)->value()->return());
        $this->assertSame('3', $this->int->mod(10)->value()->return());

        // Tinker.filter.customs.powmod
        $this->assertSame(null, $this->null->powmod(2, 10)->value()->return());
        $this->assertSame(null, $this->int->powmod(null, 10)->value()->return());
        $this->assertSame(null, $this->int->powmod(2, null)->value()->return());
        $this->assertSame('9', $this->int->powmod(2, 10)->value()->return());

        // Tinker.filter.customs.floor
        $this->assertNull($this->null->floor()->value()->return());
        $this->assertSame('1234', $this->float->floor()->value()->return());
        $this->assertSame('1234.56', $this->float->floor(2)->value()->return());
        $this->assertSame('1200', $this->float->floor(-2)->value()->return());

        // Tinker.filter.customs.round
        $this->assertNull($this->null->round()->value()->return());
        $this->assertSame('1235', $this->float->round()->value()->return());
        $this->assertSame('1234.57', $this->float->round(2)->value()->return());
        $this->assertSame('1200', $this->float->round(-2)->value()->return());

        // Tinker.filter.customs.ceil
        $this->assertNull($this->null->ceil()->value()->return());
        $this->assertSame('1235', $this->float->ceil()->value()->return());
        $this->assertSame('1234.57', $this->float->ceil(2)->value()->return());
        $this->assertSame('1300', $this->float->ceil(-2)->value()->return());

        // Tinker.filter.customs.dump
        $this->assertSame('null', $this->null->dump()->return());
        $this->assertSame(<<<EOS
array:3 [
    0 => 1,
    1 => 2,
    2 => 3
]
EOS
        , $this->array->dump()->return());
        $this->assertSame(<<<EOS
array:3 [
    0 => 1,
    1 => ***,
    2 => 3
]
EOS
        , $this->array->dump([1], '***')->return());

        // Tinker.filter.customs.invoke
        $this->assertNull($this->null->invoke('Test')->return());
        $this->assertSame('Hello Test', $this->callable->invoke('Test')->return());

        // Tinker.filter.customs.equals
        $this->assertTrue($this->null->equals(null));
        $this->assertTrue($this->null->equals(''));
        $this->assertTrue($this->int->equals(123));
        $this->assertTrue($this->int->equals('123'));
        $this->assertFalse($this->int->equals(1234));

        // Tinker.filter.customs.sameAs
        $this->assertTrue($this->null->sameAs(null));
        $this->assertFalse($this->null->sameAs(''));
        $this->assertTrue($this->int->sameAs(123));
        $this->assertFalse($this->int->sameAs('123'));
        $this->assertFalse($this->int->sameAs(1234));

        // Tinker.filter.customs.nnvl
        $this->assertSame(null, $this->null->nnvl('A')->return());
        $this->assertSame('B', $this->null->nnvl('A', 'B')->return());
        $this->assertSame('A', $this->int->nnvl('A')->return());
        $this->assertSame('A', Tinker::with('')->nnvl('A')->return());
        $this->assertSame('A', Tinker::with([])->nnvl('A')->return());
        $this->assertSame('A', Tinker::with(0)->nnvl('A')->return());

        // Tinker.filter.customs.nbvl
        $this->assertSame(null, $this->null->nbvl('A')->return());
        $this->assertSame('B', $this->null->nbvl('A', 'B')->return());
        $this->assertSame('A', $this->int->nbvl('A')->return());
        $this->assertSame(null, Tinker::with('')->nbvl('A')->return());
        $this->assertSame(null, Tinker::with([])->nbvl('A')->return());
        $this->assertSame('A', Tinker::with(0)->nbvl('A')->return());

        // Tinker.filter.customs.nevl
        $this->assertSame(null, $this->null->nevl('A')->return());
        $this->assertSame('B', $this->null->nevl('A', 'B')->return());
        $this->assertSame('A', $this->int->nevl('A')->return());
        $this->assertSame(null, Tinker::with('')->nevl('A')->return());
        $this->assertSame(null, Tinker::with([])->nevl('A')->return());
        $this->assertSame(null, Tinker::with(0)->nevl('A')->return());

        // Tinker.filter.customs.when
        $this->assertSame('A', $this->null->when(null, 'A')->return());
        $this->assertSame(null, $this->null->when(123, 'A')->return());
        $this->assertSame('A', $this->int->when(123, 'A')->return());
        $this->assertSame('A', $this->int->when(Tinker::with(123), 'A')->return());
        $this->assertSame(123, $this->int->when(234, 'A')->return());
        $this->assertSame('B', $this->int->when(234, 'A', 'B')->return());
        $this->assertSame('A', $this->int->when(function ($v) { return $v < 999; }, 'A', 'B')->return());
        $this->assertSame('A', $this->int->when(true, 'A', 'B')->return());
        $this->assertSame('B', $this->int->when(false, 'A', 'B')->return());

        // Tinker.filter.customs.case
        $case = [123 => 'A', 'Hello Rebet' => 'B'];
        $this->assertNull($this->null->case($case)->return());
        $this->assertSame('default', $this->null->case($case, 'default')->return());
        $this->assertSame('A', $this->int->case($case)->return());
        $this->assertSame('B', $this->string->case($case)->return());
        $this->assertSame("Hello\nRebet", $this->text->case($case)->return());
        $this->assertSame('C', $this->text->case($case, 'C')->return());

        // Tinker.filter.customs.length
        $this->assertSame(null, $this->null->length()->return());
        $this->assertSame(3, $this->int->length()->return());
        $this->assertSame(9, $this->float->length()->return());
        $this->assertSame(11, $this->string->length()->return());
        $this->assertSame(1, $this->enum->length()->return());
        $this->assertSame(2, $this->enums->length()->return());
        $this->assertSame(3, $this->array->length()->return());

        // Tinker.filter.customs.values
        $this->assertSame(null, $this->null->values()->return());
        $this->assertSame([123], $this->int->values()->return());
        $this->assertSame([1, 2, 3], $this->array->values()->return());
        $this->assertSame([
            'FOO',
            [
                'child' => [
                    'bar' => 'BAR',
                ],
            ],
            123,
            Gender::MALE(),
            true,
        ], $this->map->values()->return());

        // Tinker.filter.customs.keys
        $this->assertSame(null, $this->null->keys()->return());
        $this->assertSame([0], $this->int->keys()->return());
        $this->assertSame([0, 1, 2], $this->array->keys()->return());
        $this->assertSame([
            'foo',
            'parent',
            'number',
            'gender',
            'boolean',
        ], $this->map->keys()->return());


        // PHP function is_null
        $this->assertTrue($this->null->isNull());
        $this->assertFalse($this->int->isNull());

        // PHP function is_string
        $this->assertTrue($this->string->isString());
        $this->assertFalse($this->int->isString());

        // PHP function is_int
        $this->assertTrue($this->int->isInt());
        $this->assertFalse($this->string->isInt());

        // PHP function is_float
        $this->assertTrue($this->float->isFloat());
        $this->assertFalse($this->int->isFloat());

        // PHP function is_array
        $this->assertTrue($this->array->isArray());
        $this->assertFalse($this->int->isArray());

        // PHP function is_bool
        $this->assertTrue(Tinker::with(true)->isBool());
        $this->assertFalse($this->int->isBool());

        // PHP function is_callable
        $this->assertTrue($this->callable->isCallable());
        $this->assertFalse($this->int->isCallable());
    }

    public function test_filters_php()
    {
        $this->assertFalse($this->null->isInt());
        $this->assertFalse($this->null->is_int());
        $this->assertTrue($this->int->isInt());
        $this->assertTrue($this->int->is_int());

        $this->assertSame('[1,2,3]', $this->array->jsonEncode()->return());
        $this->assertSame([1, 2, 3], $this->json->jsonDecode()->return());

        $this->assertSame([1, 2, 3, 0, 0], $this->array->arrayPad(5, 0)->return());
    }

    public function test_filters_escapeError()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid escape type [xml] given. The type must be html or url");

        $this->string->escape('xml');
    }

    public function test_filters_convertError()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Apply datetimef filter failed. The origin type 'Closure' can not convert to Rebet\DateTime\DateTime.");

        $this->callable->datetimef('Y/m/d');
    }
}

class TinkerTest_DestructiveMock
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

class TinkerTest_User
{
    public $user_id;
    public $first_name;
    public $last_name;
    public $email;
    public $gender;
    public $birthday;

    public function __construct($user_id, $first_name, $last_name, $email, $gender, $birthday)
    {
        $this->user_id    = $user_id;
        $this->first_name = $first_name;
        $this->last_name  = $last_name;
        $this->email      = $email;
        $this->gender     = $gender;
        $this->birthday   = $birthday;
    }

    public function fullName() : string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function age($at = 'today') : int
    {
        return $this->birthday->age($at);
    }
}
