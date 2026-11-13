<?php

declare(strict_types=1);

namespace Rebet\Tests\Tools\Reflection;

use Override;
use Rebet\Application\App;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Configurable;
use Rebet\Tools\DateTime\DayOfWeek;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Reflection\Convertible;
use Rebet\Tools\Reflection\Describable;
use Rebet\Tools\Reflection\DotAccessDelegator;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Tinker\Tinker;
use TestApp\Enum\Gender;
use TestApp\Enum\GroupPosition;
use TestApp\Stub\JsonSerializableStub;
use TestApp\Stub\ToArrayStub;

class ReflectorTest extends RebetTestCase
{
    private $array            = null;
    private $map              = null;
    private $object           = null;
    private $transparent      = null;
    private $accessible       = null;
    private $accessible_child = null;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        App::setLocale('ja');
        $this->array = ['a', 'b', 'c', null];
        $this->map   = [
            'name'     => 'John Smith',
            'gender'   => 'male',
            'hobbies'  => ['game', 'outdoor'],
            'partner'  => [
                'name'   => 'Jane Smith',
                'gender' => 'female',
            ],
            'children' => null,
        ];
        $this->object = (object) [
            'name'     => 'John Smith',
            'gender'   => 'male',
            'hobbies'  => ['game', 'outdoor'],
            'partner'  => (object) [
                'name'   => 'Jane Smith',
                'gender' => 'female',
            ],
            'children' => null,
        ];
        $this->transparent = (object) [
            'a' => new class implements DotAccessDelegator {
                #[Override]
                public function get()
                {
                    return [
                        'b' => 'ab',
                        'c' => new class implements DotAccessDelegator {
                            #[Override]
                            public function get()
                            {
                                return 'ac';
                            }
                        },
                    ];
                }
            },
            'b' => new class implements DotAccessDelegator {
                #[Override]
                public function get()
                {
                    return new class implements DotAccessDelegator {
                        #[Override]
                        public function get()
                        {
                            return 'b';
                        }
                    };
                }
            },
        ];

        $this->accessible                = new ReflectorTest_Accessible();
        $this->accessible->public_parent = new ReflectorTest_Accessible();
        $this->accessible->setPrivateParent(new ReflectorTest_Accessible());

        $this->accessible_child                = new ReflectorTest_AccessibleChild();
        $this->accessible_child->public_parent = new ReflectorTest_AccessibleChild();
        $this->accessible_child->setPrivateParent(new ReflectorTest_AccessibleChild());

        $this->vfs([
            'dummy.txt' => 'dummy',
        ]);
    }

    public function test_instantiate(): void
    {
        $this->assertNull(Reflector::instantiate(null));
        $this->assertNull(Reflector::instantiate(''));
        $this->assertNull(Reflector::instantiate([]));

        $this->assertSame('default', Reflector::instantiate(ReflectorTest_Mock::class)->value);
        $this->assertSame('via getInstance()', Reflector::instantiate(ReflectorTest_Mock::class . '::getInstance')->value);
        $this->assertSame('arg', Reflector::instantiate([ReflectorTest_Mock::class, 'arg'])->value);
        $this->assertSame('arg', Reflector::instantiate([ReflectorTest_Mock::class, 'value' => 'arg'])->value);
        $this->assertSame('arg', Reflector::instantiate(['@factory' => ReflectorTest_Mock::class, 'value' => 'arg'])->value);
        $this->assertSame('setup', Reflector::instantiate([
            '@factory' => ReflectorTest_Mock::class,
            '@setup'   => function ($mock) {
                $mock->value = 'setup';
                return $mock;
            },
        ])->value);
        $this->assertSame('arg via build()', Reflector::instantiate([ReflectorTest_Mock::class . '::build', 'arg'])->value);
        $this->assertSame('arg via build()', Reflector::instantiate([ReflectorTest_Mock::class . '::build', 'value' => 'arg'])->value);
        $this->assertSame('arg via build()', Reflector::instantiate(['@factory' => ReflectorTest_Mock::class . '::build', 'value' => 'arg'])->value);
        $this->assertSame('arg via build() setup', Reflector::instantiate([
            '@factory' => ReflectorTest_Mock::class . '::build',
            'value'    => 'arg',
            '@setup'   => function ($mock) {
                $mock->value .= ' setup';
                return $mock;
            },
        ])->value);
        $this->assertSame(123, Reflector::instantiate(123));
        $this->assertSame('instantiated', Reflector::instantiate(new ReflectorTest_Mock('instantiated'))->value);
    }

    public function test_get_enumValue(): void
    {
        // enum is immutable (the read-only name and value), so that it must be returned as it is
        $array = ['backed' => ReflectorTest_IntEnum::One, 'pure' => ReflectorTest_PureEnum::Alpha, 'nested' => ['enum' => ReflectorTest_IntEnum::Zero]];
        $this->assertSame(ReflectorTest_IntEnum::One, Reflector::get($array, 'backed'));
        $this->assertSame(ReflectorTest_PureEnum::Alpha, Reflector::get($array, 'pure'));
        $this->assertSame(ReflectorTest_IntEnum::Zero, Reflector::get($array, 'nested.enum'));
        $this->assertSame(ReflectorTest_IntEnum::One, Reflector::get((object) $array, 'backed'));
        $this->assertSame($array['backed'], Reflector::get($array, 'backed'));
        $this->assertSame('default', Reflector::get($array, 'nothing', 'default'));
    }

    public function test_get(): void
    {
        $this->assertSame($this->array, Reflector::get($this->array, null));
        $this->assertSame($this->array, Reflector::get($this->array, ''));
        $this->assertNull(Reflector::get($this->array, 'invalid_key'));
        $this->assertSame('default', Reflector::get($this->array, 'invalid_key', 'default'));
        $this->assertSame('a', Reflector::get($this->array, 0, 'default'));
        $this->assertSame('default', Reflector::get($this->array, 3, 'default'));
        $this->assertSame('default', Reflector::get($this->array, 4, 'default'));

        $this->assertSame($this->map, Reflector::get($this->map, null));
        $this->assertSame($this->map, Reflector::get($this->map, ''));
        $this->assertSame('default', Reflector::get($this->map, 'invalid_key', 'default'));
        $this->assertSame('John Smith', Reflector::get($this->map, 'name', 'default'));
        $this->assertSame(['game', 'outdoor'], Reflector::get($this->map, 'hobbies', []));
        $this->assertSame('game', Reflector::get($this->map, 'hobbies.0', 'default'));
        $this->assertSame('default', Reflector::get($this->map, 'hobbies.3', 'default'));
        $this->assertSame('default', Reflector::get($this->map, 'hobbies.invalid_key', 'default'));
        $this->assertSame('Jane Smith', Reflector::get($this->map, 'partner.name', 'default'));
        $this->assertSame('female', Reflector::get($this->map, 'partner.gender', 'default'));
        $this->assertSame('default', Reflector::get($this->map, 'partner.invalid_key', 'default'));
        $this->assertSame('default', Reflector::get($this->map, 'partner.gender.invalid_key', 'default'));
        $this->assertSame([], Reflector::get($this->map, 'children', []));

        $this->assertSame($this->object, Reflector::get($this->object, null));
        $this->assertSame($this->object, Reflector::get($this->object, ''));
        $this->assertSame('default', Reflector::get($this->object, 'invalid_key', 'default'));
        $this->assertSame('John Smith', Reflector::get($this->object, 'name', 'default'));
        $this->assertSame(['game', 'outdoor'], Reflector::get($this->object, 'hobbies', []));
        $this->assertSame('game', Reflector::get($this->object, 'hobbies.0', 'default'));
        $this->assertSame('default', Reflector::get($this->object, 'hobbies.3', 'default'));
        $this->assertSame('default', Reflector::get($this->object, 'hobbies.invalid_key', 'default'));
        $this->assertSame('Jane Smith', Reflector::get($this->object, 'partner.name', 'default'));
        $this->assertSame('female', Reflector::get($this->object, 'partner.gender', 'default'));
        $this->assertSame('default', Reflector::get($this->object, 'partner.invalid_key', 'default'));
        $this->assertSame('default', Reflector::get($this->object, 'partner.gender.invalid_key', 'default'));
        $this->assertSame([], Reflector::get($this->object, 'children', []));

        $this->assertSame(
            [
                'b' => 'ab',
                'c' => 'ac',
            ],
            Reflector::get($this->transparent->a, null),
        );
        $this->assertSame('b', Reflector::get($this->transparent->b, null));
        $this->assertSame('b', Reflector::get($this->transparent->b, ''));
        $this->assertSame('ab', Reflector::get($this->transparent, 'a.b'));
        $this->assertSame('ac', Reflector::get($this->transparent, 'a.c'));
        $this->assertSame('b', Reflector::get($this->transparent, 'b'));

        $name = Tinker::with('Bob');
        $view = (object) ['data' => ['name' => $name]];
        $this->assertSame($name, Reflector::get($view, 'data.name', null, true));

        $this->assertSame('private', Reflector::get($this->accessible, 'private', null, true));
        $this->assertSame('protected', Reflector::get($this->accessible, 'protected', null, true));
        $this->assertSame('public', Reflector::get($this->accessible, 'public'));
        $this->assertSame('private', Reflector::get($this->accessible, 'public_parent.private', null, true));
        $this->assertSame('protected', Reflector::get($this->accessible, 'public_parent.protected', null, true));
        $this->assertSame('public', Reflector::get($this->accessible, 'public_parent.public'));
        $this->assertSame('private', Reflector::get($this->accessible, 'private_parent.private', null, true));
        $this->assertSame('protected', Reflector::get($this->accessible, 'private_parent.protected', null, true));
        $this->assertSame('public', Reflector::get($this->accessible, 'private_parent.public', null, true));

        $this->assertSame('private', Reflector::get($this->accessible_child, 'private', null, true));
        $this->assertSame('protected', Reflector::get($this->accessible_child, 'protected', null, true));
        $this->assertSame('public', Reflector::get($this->accessible_child, 'public'));
        $this->assertSame('private', Reflector::get($this->accessible_child, 'public_parent.private', null, true));
        $this->assertSame('protected', Reflector::get($this->accessible_child, 'public_parent.protected', null, true));
        $this->assertSame('public', Reflector::get($this->accessible_child, 'public_parent.public'));
        $this->assertSame('private', Reflector::get($this->accessible_child, 'private_parent.private', null, true));
        $this->assertSame('protected', Reflector::get($this->accessible_child, 'private_parent.protected', null, true));
        $this->assertSame('public', Reflector::get($this->accessible_child, 'private_parent.public', null, true));

        $this->assertSame(null, Reflector::get(ReflectorTest_Accessible::class, 'static_private'));
        $this->assertSame(null, Reflector::get(ReflectorTest_Accessible::class, 'static_protected'));
        $this->assertSame("static_public", Reflector::get(ReflectorTest_Accessible::class, 'static_public'));
        $this->assertSame(null, Reflector::get(ReflectorTest_Accessible::class, 'nothing'));
        $this->assertSame(null, Reflector::get(ReflectorTest_AccessibleChild::class, 'static_private'));
        $this->assertSame(null, Reflector::get(ReflectorTest_AccessibleChild::class, 'static_protected'));
        $this->assertSame("static_public", Reflector::get(ReflectorTest_AccessibleChild::class, 'static_public'));
        $this->assertSame(null, Reflector::get(ReflectorTest_AccessibleChild::class, 'nothing'));
        $this->assertSame(null, Reflector::get("NothingClass", 'foo'));

        $this->assertSame("static_private", Reflector::get(ReflectorTest_Accessible::class, 'static_private', null, true));
        $this->assertSame("static_protected", Reflector::get(ReflectorTest_Accessible::class, 'static_protected', null, true));
        $this->assertSame("static_public", Reflector::get(ReflectorTest_Accessible::class, 'static_public', null, true));
        $this->assertSame(null, Reflector::get(ReflectorTest_Accessible::class, 'nothing', null, true));
        $this->assertSame("static_private", Reflector::get(ReflectorTest_AccessibleChild::class, 'static_private', null, true));
        $this->assertSame("static_protected", Reflector::get(ReflectorTest_AccessibleChild::class, 'static_protected', null, true));
        $this->assertSame("static_public", Reflector::get(ReflectorTest_AccessibleChild::class, 'static_public', null, true));
        $this->assertSame(null, Reflector::get(ReflectorTest_AccessibleChild::class, 'nothing', null, true));
        $this->assertSame(null, Reflector::get("NothingClass", 'foo', null, true));
    }

    public function test_get_has_enumeration(): void
    {
        // Enumeration: the field or metadata is accessible (the translated value if the field is given to the Translatable attribute)
        $this->assertSame(1, Reflector::get(Gender::Male, 'value'));
        $this->assertSame('Male', Reflector::get(Gender::Male, 'name'));
        $this->assertSame('男性', Reflector::get(Gender::Male, 'label'));
        $this->assertSame('Manager', Reflector::get(GroupPosition::Manager, 'label'));
        $this->assertSame('日曜', Reflector::get(DayOfWeek::Sunday, 'label_short'));
        $this->assertSame('default', Reflector::get(Gender::Male, 'nothing', 'default'));
        $this->assertNull(Reflector::get(Gender::Male, 'nothing'));
        $this->assertSame('男性', Reflector::get(['gender' => Gender::Male], 'gender.label'));

        $this->assertTrue(Reflector::has(Gender::Male, 'value'));
        $this->assertTrue(Reflector::has(Gender::Male, 'name'));
        $this->assertTrue(Reflector::has(Gender::Male, 'label'));
        $this->assertTrue(Reflector::has(DayOfWeek::Sunday, 'label_short'));
        $this->assertFalse(Reflector::has(Gender::Male, 'nothing'));
        $this->assertTrue(Reflector::has(['gender' => Gender::Male], 'gender.label'));
        $this->assertFalse(Reflector::has(['gender' => Gender::Male], 'gender.nothing'));
    }

    public function test_reflectProperty(): void
    {
        $isPublic = fn($object, string $property) => Reflector::reflectProperty($object, $property)?->isPublic();

        $child = new ReflectorTest_AccessibleChild();
        $this->assertTrue($isPublic($child, 'public'));
        $this->assertTrue($isPublic($child, 'public_parent'));      // the public property of the ancestor class
        $this->assertFalse($isPublic($child, 'protected'));
        $this->assertFalse($isPublic($child, 'private'));
        $this->assertFalse($isPublic($child, 'private_parent'));    // the private property of the ancestor class is also found
        $this->assertNull($isPublic($child, 'nothing'));
        $this->assertNull(Reflector::reflectProperty($child, 'nothing'));

        $child->dynamic = 'dynamic';
        $this->assertTrue($isPublic($child, 'dynamic'));

        // class name and static property
        $this->assertTrue($isPublic(ReflectorTest_AccessibleChild::class, 'static_public'));
        $this->assertFalse($isPublic(ReflectorTest_AccessibleChild::class, 'static_protected'));
        $this->assertFalse($isPublic(ReflectorTest_AccessibleChild::class, 'static_private'));

        // not an object nor an existing class
        $this->assertNull(Reflector::reflectProperty('NotExistsClass', 'public'));
        $this->assertNull(Reflector::reflectProperty([], 'public'));

        // native enum has only name and value
        $this->assertTrue($isPublic(Gender::Male, 'value'));
        $this->assertNull($isPublic(Gender::Male, 'label'));
    }

    public function test_has(): void
    {
        $this->assertFalse(Reflector::has(null, 'invalid_key'));
        $this->assertFalse(Reflector::has('string', 'invalid_key'));

        $this->assertFalse(Reflector::has($this->array, 'invalid_key'));
        $this->assertTrue(Reflector::has($this->array, 0));
        $this->assertTrue(Reflector::has($this->array, 3));
        $this->assertFalse(Reflector::has($this->array, 4));

        $this->assertFalse(Reflector::has($this->map, 'invalid_key'));
        $this->assertTrue(Reflector::has($this->map, 'name'));
        $this->assertTrue(Reflector::has($this->map, 'hobbies'));
        $this->assertTrue(Reflector::has($this->map, 'hobbies.0'));
        $this->assertFalse(Reflector::has($this->map, 'hobbies.3'));
        $this->assertFalse(Reflector::has($this->map, 'hobbies.invalid_key'));
        $this->assertTrue(Reflector::has($this->map, 'partner.name'));
        $this->assertTrue(Reflector::has($this->map, 'partner.gender'));
        $this->assertFalse(Reflector::has($this->map, 'partner.invalid_key'));
        $this->assertFalse(Reflector::has($this->map, 'partner.gender.invalid_key'));
        $this->assertTrue(Reflector::has($this->map, 'children'));
        $this->assertFalse(Reflector::has($this->map, 'children.0'));

        $this->assertFalse(Reflector::has($this->object, 'invalid_key'));
        $this->assertTrue(Reflector::has($this->object, 'name'));
        $this->assertTrue(Reflector::has($this->object, 'hobbies'));
        $this->assertTrue(Reflector::has($this->object, 'hobbies.0'));
        $this->assertFalse(Reflector::has($this->object, 'hobbies.3'));
        $this->assertFalse(Reflector::has($this->object, 'hobbies.invalid_key'));
        $this->assertTrue(Reflector::has($this->object, 'partner.name'));
        $this->assertTrue(Reflector::has($this->object, 'partner.gender'));
        $this->assertFalse(Reflector::has($this->object, 'partner.invalid_key'));
        $this->assertFalse(Reflector::has($this->object, 'partner.gender.invalid_key'));
        $this->assertTrue(Reflector::has($this->object, 'children'));
        $this->assertFalse(Reflector::has($this->object, 'children.0'));

        $this->assertTrue(Reflector::has($this->transparent, 'a'));
        $this->assertTrue(Reflector::has($this->transparent, 'a.b'));
        $this->assertFalse(Reflector::has($this->transparent, 'a.b.c'));
        $this->assertTrue(Reflector::has($this->transparent, 'a.c'));
        $this->assertFalse(Reflector::has($this->transparent, 'a.c.d'));
        $this->assertFalse(Reflector::has($this->transparent, 'a.d'));
        $this->assertTrue(Reflector::has($this->transparent, 'b'));
        $this->assertFalse(Reflector::has($this->transparent, 'b.c'));

        $this->assertFalse(Reflector::has(ReflectorTest_Accessible::class, 'static_private'));
        $this->assertFalse(Reflector::has(ReflectorTest_Accessible::class, 'static_protected'));
        $this->assertTrue(Reflector::has(ReflectorTest_Accessible::class, 'static_public'));
        $this->assertFalse(Reflector::has(ReflectorTest_Accessible::class, 'nothing'));
        $this->assertFalse(Reflector::has("NothingClass", 'foo'));

        $this->assertTrue(Reflector::has(ReflectorTest_Accessible::class, 'static_private', true));
        $this->assertTrue(Reflector::has(ReflectorTest_Accessible::class, 'static_protected', true));
        $this->assertTrue(Reflector::has(ReflectorTest_Accessible::class, 'static_public', true));
        $this->assertFalse(Reflector::has(ReflectorTest_Accessible::class, 'nothing', true));
        $this->assertFalse(Reflector::has("NothingClass", 'foo', true));
    }

    public function test_set(): void
    {
        Reflector::set($this->array, null, 'd');
        $this->assertSame('d', $this->array[4]);

        Reflector::set($this->array, 0, 'A');
        $this->assertSame('A', $this->array[0]);

        Reflector::set($this->array, '1', 'B');
        $this->assertSame('B', $this->array[1]);


        Reflector::set($this->map, 'name', 'Charles Babbage');
        $this->assertSame('Charles Babbage', $this->map['name']);

        Reflector::set($this->map, 'hobbies.0', 'cycling');
        $this->assertSame('cycling', $this->map['hobbies'][0]);
        $this->assertSame(['cycling', 'outdoor'], $this->map['hobbies']);

        Reflector::set($this->map, 'hobbies', ['game']);
        $this->assertSame('game', $this->map['hobbies'][0]);
        $this->assertSame(['game'], $this->map['hobbies']);

        Reflector::set($this->map, 'partner.name', 'Georgiana Whitmore');
        $this->assertSame('Georgiana Whitmore', $this->map['partner']['name']);


        Reflector::set($this->object, 'name', 'Charles Babbage');
        $this->assertSame('Charles Babbage', $this->object->name);

        Reflector::set($this->object, 'hobbies.0', 'cycling');
        $this->assertSame('cycling', $this->object->hobbies[0]);
        $this->assertSame(['cycling', 'outdoor'], $this->object->hobbies);

        Reflector::set($this->object, 'hobbies', ['game']);
        $this->assertSame('game', $this->object->hobbies[0]);
        $this->assertSame(['game'], $this->object->hobbies);

        Reflector::set($this->object, 'partner.name', 'Georgiana Whitmore');
        $this->assertSame('Georgiana Whitmore', $this->object->partner->name);

        Reflector::set($this->transparent, 'a.b', 'AB');
        $this->assertSame(Reflector::get($this->transparent, 'a.b'), 'AB');

        Reflector::set($this->transparent, 'a.c', 'AC');
        $this->assertSame(Reflector::get($this->transparent, 'a.c'), 'AC');

        Reflector::set($this->transparent, 'b', 'B');
        $this->assertSame(Reflector::get($this->transparent, 'b'), 'B');

        $class = ReflectorTest_Accessible::class;
        Reflector::set($class, 'static_public', 'updated');
        $this->assertSame(Reflector::get($class, 'static_public'), 'updated');

        Reflector::set($class, 'static_private', 'updated', true);
        $this->assertSame(Reflector::get($class, 'static_private', null, true), 'updated');

        Reflector::set($class, 'static_protected', 'updated', true);
        $this->assertSame(Reflector::get($class, 'static_protected', null, true), 'updated');
    }

    public function test_remove(): void
    {
        $this->assertTrue(isset($this->array[0]));
        $this->assertSame('a', Reflector::remove($this->array, 0));
        $this->assertFalse(isset($this->array[0]));


        $this->assertTrue(isset($this->map['name']));
        $this->assertSame('John Smith', Reflector::remove($this->map, 'name'));
        $this->assertFalse(isset($this->map['name']));

        $this->assertTrue(isset($this->map['hobbies'][0]));
        $this->assertSame('game', Reflector::remove($this->map, 'hobbies.0'));
        $this->assertFalse(isset($this->map['hobbies'][0]));

        $this->assertTrue(isset($this->map['hobbies']));
        $this->assertSame([1 => 'outdoor'], Reflector::remove($this->map, 'hobbies'));
        $this->assertFalse(isset($this->map['hobbies']));

        $this->assertTrue(isset($this->map['partner']['name']));
        $this->assertSame('Jane Smith', Reflector::remove($this->map, 'partner.name'));
        $this->assertFalse(isset($this->map['partner']['name']));


        $this->assertTrue(property_exists($this->object, 'name'));
        $this->assertSame('John Smith', Reflector::remove($this->object, 'name'));
        $this->assertFalse(property_exists($this->object, 'name'));

        $this->assertTrue(isset($this->object->hobbies[0]));
        $this->assertSame('game', Reflector::remove($this->object, 'hobbies.0'));
        $this->assertFalse(isset($this->object->hobbies[0]));

        $this->assertTrue(property_exists($this->object, 'hobbies'));
        $this->assertSame([1 => 'outdoor'], Reflector::remove($this->object, 'hobbies'));
        $this->assertFalse(property_exists($this->object, 'hobbies'));

        $this->assertTrue(property_exists($this->object->partner, 'name'));
        $this->assertSame('Jane Smith', Reflector::remove($this->object, 'partner.name'));
        $this->assertFalse(property_exists($this->object->partner, 'name'));

        $this->assertSame('ab', Reflector::remove($this->transparent, 'a.b'));
        $this->assertSame(null, Reflector::get($this->transparent, 'a.b'));

        $this->assertSame('ac', Reflector::remove($this->transparent, 'a.c'));
        $this->assertSame(null, Reflector::get($this->transparent, 'a.c'));

        $this->assertSame('b', Reflector::remove($this->transparent, 'b'));
        $this->assertSame(null, Reflector::get($this->transparent, 'b'));
    }

    /**
     * Regression tests for a bug where Reflector::get()/has()/set()/remove() always treated an
     * `\ArrayAccess` (and `\Traversable` and `\Countable`) object as a pure array container,
     * so a key that was not a valid array item was reported as missing even when the object
     * actually had a real property of that name (e.g. Rebet\Database\Pagination\Paginator,
     * whose parent Rebet\Database\ResultSet implements all three).
     *
     * An array item that genuinely exists must still take priority over a same-named property.
     */
    public function test_get_arrayAccessibleObjectFallsBackToProperty(): void
    {
        $target    = new ReflectorTest_ArrayAccessible();
        $target[0] = 'item0';

        // An existing array item still takes priority.
        $this->assertSame('item0', Reflector::get($target, 0));

        // A key that is not a valid array item must fall through to the real object property.
        $this->assertSame('public', Reflector::get($target, 'public'));
        $this->assertNull(Reflector::get($target, 'protected'));
        $this->assertSame('protected', Reflector::get($target, 'protected', null, true));
        $this->assertNull(Reflector::get($target, 'private'));
        $this->assertSame('private', Reflector::get($target, 'private', null, true));

        // Neither an array item nor a property.
        $this->assertNull(Reflector::get($target, 'nothing'));
        $this->assertSame('default', Reflector::get($target, 'nothing', 'default'));
    }

    public function test_has_arrayAccessibleObjectFallsBackToProperty(): void
    {
        $target    = new ReflectorTest_ArrayAccessible();
        $target[0] = 'item0';

        $this->assertTrue(Reflector::has($target, 0));
        $this->assertTrue(Reflector::has($target, 'public'));
        $this->assertFalse(Reflector::has($target, 'protected'));
        $this->assertTrue(Reflector::has($target, 'protected', true));
        $this->assertFalse(Reflector::has($target, 'nothing'));
    }

    public function test_set_arrayAccessibleObjectFallsBackToProperty(): void
    {
        $target    = new ReflectorTest_ArrayAccessible();
        $target[0] = 'item0';

        // An existing array item still takes priority.
        Reflector::set($target, 0, 'updated item0');
        $this->assertSame('updated item0', $target[0]);
        $this->assertSame('public', $target->public);

        // A key that is not a valid array item must be written to the real property, not the
        // items container.
        Reflector::set($target, 'public', 'updated public');
        $this->assertSame('updated public', $target->public);
        $this->assertFalse(isset($target['public']));
        $this->assertSame('updated public', Reflector::get($target, 'public'));

        Reflector::set($target, 'protected', 'updated protected', true);
        $this->assertSame('updated protected', Reflector::get($target, 'protected', null, true));
        $this->assertFalse(isset($target['protected']));

        // Neither an array item nor a property: falls back to creating a new array item, just
        // like setting a brand-new key on a plain array.
        Reflector::set($target, 'nothing', 'new item');
        $this->assertSame('new item', $target['nothing']);

        $target['public'] = 'item public';
        $this->assertSame('updated public', $target->public);
        $this->assertSame('item public', $target['public']);
        $this->assertSame('item public', Reflector::get($target, 'public'));
        Reflector::set($target, 'public', 'updated item public');
        $this->assertSame('updated public', $target->public);
        $this->assertSame('updated item public', $target['public']);
        $this->assertSame('updated item public', Reflector::get($target, 'public'));
    }

    public function test_remove_arrayAccessibleObjectFallsBackToProperty(): void
    {
        $target    = new ReflectorTest_ArrayAccessible();
        $target[0] = 'item0';

        // An existing array item still takes priority.
        $this->assertSame('item0', Reflector::remove($target, 0));
        $this->assertFalse(isset($target[0]));
        $this->assertSame('public', $target->public);

        // A public property that is not a valid array item is removed via the real property.
        $this->assertSame('public', Reflector::remove($target, 'public'));
        $this->assertFalse(isset($target->public));

        // A non-public property can not be removed, per remove()'s "public properties only"
        // contract, even with $accessible=true.
        $this->expectException(\OutOfBoundsException::class);
        Reflector::remove($target, 'protected', true);
    }

    public function test_set_undefindKeyArray(): void
    {
        Reflector::set($this->array, 'undefind_key', 'value');
        $this->assertSame('value', Reflector::get($this->array, 'undefind_key'));
    }

    public function test_set_nestedUndefindKeyArray(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage("Nested parent key 'undefind_key' does not exist.");

        Reflector::set($this->map, 'undefind_key.name', 'value');
    }

    public function test_set_nestedTerminateUndefindKeyArray(): void
    {
        Reflector::set($this->map, 'partner.undefind_key', 'value');
        $this->assertSame('value', Reflector::get($this->map, 'partner.undefind_key'));
    }

    public function test_set_undefindKeyObject(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage("Nested key 'undefind_key' does not exist.");

        Reflector::set($this->object, 'undefind_key', 'value');
    }

    public function test_set_nestedTerminateUndefindKeyObject(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage("Nested key 'undefind_key' does not exist.");

        Reflector::set($this->object, 'partner.undefind_key', 'value');
    }

    public function test_set_nestedUndefindKeyObject(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage("Nested key 'undefind_key' does not exist.");

        Reflector::set($this->object, 'undefind_key.partner', 'value');
    }

    public function test_set_accessible(): void
    {
        Reflector::set($this->accessible, 'public', 'value');
        $this->assertSame('value', Reflector::get($this->accessible, 'public'));

        Reflector::set($this->accessible, 'public', 'value', true);
        $this->assertSame('value', Reflector::get($this->accessible, 'public'));

        Reflector::set($this->accessible, 'protected', 'value', true);
        $this->assertSame('value', Reflector::get($this->accessible, 'protected', null, true));

        Reflector::set($this->accessible, 'private', 'value', true);
        $this->assertSame('value', Reflector::get($this->accessible, 'private', null, true));

        Reflector::set($this->accessible, 'public_parent.public', 'value');
        $this->assertSame('value', Reflector::get($this->accessible, 'public_parent.public'));

        Reflector::set($this->accessible, 'public_parent.private', 'value', true);
        $this->assertSame('value', Reflector::get($this->accessible, 'public_parent.private', null, true));

        Reflector::set($this->accessible, 'private_parent.public', 'value', true);
        $this->assertSame('value', Reflector::get($this->accessible, 'private_parent.public', null, true));

        Reflector::set($this->accessible, 'private_parent.private', 'value', true);
        $this->assertSame('value', Reflector::get($this->accessible, 'private_parent.private', null, true));
    }

    public function test_set_notAccessible(): void
    {
        // The non-public property can not be set unless the accessible is given (ReflectionProperty::setAccessible() has no effect since PHP 8.1)
        foreach ([$this->accessible, $this->accessible_child] as $object) {
            foreach (['protected', 'private'] as $property) {
                try {
                    Reflector::set($object, $property, 'modified');
                    $this->fail("Must not execute. ({$property})");
                } catch (\OutOfBoundsException $e) {
                    $this->assertSame("Nested key '{$property}' can not access.", $e->getMessage());
                }
                $this->assertNotSame('modified', Reflector::get($object, $property, null, true));
            }

            // The non-public property on the way of the nested key is also not accessible.
            try {
                Reflector::set($object, 'private_parent.public', 'modified');
                $this->fail("Must not execute. (nested)");
            } catch (\OutOfBoundsException $e) {
                $this->assertSame("Nested key 'private_parent' can not access.", $e->getMessage());
            }
        }
    }

    public function test_remove_notAccessible(): void
    {
        try {
            Reflector::remove($this->accessible, 'private_parent.public');
            $this->fail("Must not execute.");
        } catch (\OutOfBoundsException $e) {
            $this->assertSame("Nested key 'private_parent' can not access.", $e->getMessage());
        }
    }

    public function test_invoke_notAccessible(): void
    {
        foreach ([$this->accessible, $this->accessible_child, ReflectorTest_Accessible::class] as $object) {
            foreach (['callProtected', 'callPrivate', 'callStaticProtected', 'callStaticPrivate'] as $method) {
                try {
                    Reflector::invoke($object, $method);
                    $this->fail("Must not execute. ({$method})");
                } catch (\ReflectionException $e) {
                    $this->assertStringStartsWith("Trying to invoke non-public method ", $e->getMessage());
                    $this->assertStringContainsString("::{$method}()", $e->getMessage());
                }
            }
        }
    }

    public function test_set_accessible_child(): void
    {
        Reflector::set($this->accessible_child, 'public', 'value');
        $this->assertSame('value', Reflector::get($this->accessible_child, 'public'));

        Reflector::set($this->accessible_child, 'public', 'value', true);
        $this->assertSame('value', Reflector::get($this->accessible_child, 'public'));

        Reflector::set($this->accessible_child, 'protected', 'value', true);
        $this->assertSame('value', Reflector::get($this->accessible_child, 'protected', null, true));

        Reflector::set($this->accessible_child, 'private', 'value', true);
        $this->assertSame('value', Reflector::get($this->accessible_child, 'private', null, true));

        Reflector::set($this->accessible_child, 'public_parent.public', 'value');
        $this->assertSame('value', Reflector::get($this->accessible_child, 'public_parent.public'));

        Reflector::set($this->accessible_child, 'public_parent.private', 'value', true);
        $this->assertSame('value', Reflector::get($this->accessible_child, 'public_parent.private', null, true));

        Reflector::set($this->accessible_child, 'private_parent.public', 'value', true);
        $this->assertSame('value', Reflector::get($this->accessible_child, 'private_parent.public', null, true));

        Reflector::set($this->accessible_child, 'private_parent.private', 'value', true);
        $this->assertSame('value', Reflector::get($this->accessible_child, 'private_parent.private', null, true));
    }

    public function test_invoke(): void
    {
        $this->assertSame('public', Reflector::invoke($this->accessible, 'callPublic'));
        $this->assertSame('protected', Reflector::invoke($this->accessible, 'callProtected', [], true));
        $this->assertSame('private', Reflector::invoke($this->accessible, 'callPrivate', [], true));
        $this->assertSame('public', Reflector::invoke($this->accessible, 'callStaticPublic'));
        $this->assertSame('protected', Reflector::invoke($this->accessible, 'callStaticProtected', [], true));
        $this->assertSame('private', Reflector::invoke($this->accessible, 'callStaticPrivate', [], true));
        $this->assertSame('public - 123', Reflector::invoke($this->accessible, 'callPublicWithArgs', [123]));
        $this->assertSame('public - 123', Reflector::invoke($this->accessible, 'callPublicWithArgs', ['arg' => 123]));
        $this->assertSame('public - 20 years old 男性', Reflector::invoke($this->accessible, 'callPublicWithTypeHintingArgs', ['gender' => Gender::Male, 'age' => 20]));
        $this->assertSame('public - 20 years old 男性', Reflector::invoke($this->accessible, 'callPublicWithTypeHintingArgs', ['gender' => 1, 'age' => '20'], false, true));
        $this->assertSame('public - 20 years old 男性', Reflector::invoke($this->accessible, 'callPublicWithTypeHintingArgs', ['age' => '20', 'gender' => 1], false, true));
        $this->assertSame('public - 20 years old 男性', Reflector::invoke($this->accessible, 'callPublicWithTypeHintingArgs', [1, '20'], false, true));
    }

    public function test_invoke_child(): void
    {
        $this->assertSame('public', Reflector::invoke($this->accessible_child, 'callPublic'));
        $this->assertSame('protected', Reflector::invoke($this->accessible_child, 'callProtected', [], true));
        $this->assertSame('private', Reflector::invoke($this->accessible_child, 'callPrivate', [], true));
        $this->assertSame('public', Reflector::invoke($this->accessible_child, 'callStaticPublic'));
        $this->assertSame('protected', Reflector::invoke($this->accessible_child, 'callStaticProtected', [], true));
        $this->assertSame('private', Reflector::invoke($this->accessible_child, 'callStaticPrivate', [], true));
        $this->assertSame('public - 123', Reflector::invoke($this->accessible_child, 'callPublicWithArgs', [123]));
        $this->assertSame('public - 123', Reflector::invoke($this->accessible_child, 'callPublicWithArgs', ['arg' => 123]));
        $this->assertSame('public - 20 years old 男性', Reflector::invoke($this->accessible_child, 'callPublicWithTypeHintingArgs', ['gender' => Gender::Male, 'age' => 20]));
        $this->assertSame('public - 20 years old 男性', Reflector::invoke($this->accessible_child, 'callPublicWithTypeHintingArgs', ['gender' => 1, 'age' => '20'], false, true));
        $this->assertSame('public - 20 years old 男性', Reflector::invoke($this->accessible_child, 'callPublicWithTypeHintingArgs', ['age' => '20', 'gender' => 1], false, true));
        $this->assertSame('public - 20 years old 男性', Reflector::invoke($this->accessible_child, 'callPublicWithTypeHintingArgs', [1, '20'], false, true));
    }

    public function test_evaluate(): void
    {
        $function = fn() => 'foo';
        $this->assertSame('foo', Reflector::evaluate($function));

        $function = fn($val = 'default') => $val;
        $this->assertSame('default', Reflector::evaluate($function));
        $this->assertSame(123, Reflector::evaluate($function, [123]));

        $function = fn(string $val = 'default') => $val;
        $this->assertSame('123', Reflector::evaluate($function, [123], true));

        $function = fn(Gender $gender = null, int $age = 20) => "{$age} years old " . ($gender ?? Gender::Male)->label();
        $this->assertSame('20 years old 男性', Reflector::evaluate($function));
        $this->assertSame('20 years old 女性', Reflector::evaluate($function, [Gender::Female]));
        $this->assertSame('18 years old 女性', Reflector::evaluate($function, ['gender' => Gender::Female, 'age' => 18]));
        $this->assertSame('18 years old 女性', Reflector::evaluate($function, ['gender' => 2, 'age' => 18], true));
        $this->assertSame('18 years old 男性', Reflector::evaluate($function, ['age' => 18]));
    }

    public function test_create(): void
    {
        $instance = Reflector::create(ReflectorTest_CreateDefault::class);
        $this->assertInstanceOf(ReflectorTest_CreateDefault::class, $instance);
        $this->assertSame('Foo', $instance->foo);
        $this->assertSame('Bar', $instance->bar);

        $instance = Reflector::create(ReflectorTest_CreateDefault::class, ['FOO']);
        $this->assertInstanceOf(ReflectorTest_CreateDefault::class, $instance);
        $this->assertSame('FOO', $instance->foo);
        $this->assertSame('Bar', $instance->bar);

        $instance = Reflector::create(ReflectorTest_CreateDefault::class, ['bar' => 'BAR']);
        $this->assertInstanceOf(ReflectorTest_CreateDefault::class, $instance);
        $this->assertSame('Foo', $instance->foo);
        $this->assertSame('BAR', $instance->bar);

        $instance = Reflector::create(ReflectorTest_CreateExtends::class);
        $this->assertInstanceOf(ReflectorTest_CreateExtends::class, $instance);
        $this->assertSame('Foo', $instance->foo);
        $this->assertSame('Bar', $instance->bar);

        $instance = Reflector::create(ReflectorTest_CreateExtends::class, ['foo']);
        $this->assertInstanceOf(ReflectorTest_CreateExtends::class, $instance);
        $this->assertSame('foo', $instance->foo);
        $this->assertSame('Bar', $instance->bar);

        $instance = Reflector::create(ReflectorTest_CreateExtends::class, ['bar' => 'BAR']);
        $this->assertInstanceOf(ReflectorTest_CreateExtends::class, $instance);
        $this->assertSame('Foo', $instance->foo);
        $this->assertSame('BAR', $instance->bar);
    }

    public function test_typeOf(): void
    {
        $this->assertTrue(Reflector::typeOf(null, null));
        $this->assertFalse(Reflector::typeOf(null, 'int'));
        $this->assertFalse(Reflector::typeOf(null, ReflectorTest_Mock::class));

        $this->assertTrue(Reflector::typeOf(1, null));
        $this->assertTrue(Reflector::typeOf(1, 'int'));
        $this->assertFalse(Reflector::typeOf(1, 'float'));
        $this->assertFalse(Reflector::typeOf(1, 'bool'));
        $this->assertFalse(Reflector::typeOf(1, 'array'));
        $this->assertFalse(Reflector::typeOf(1, 'string'));
        $this->assertFalse(Reflector::typeOf(1, 'callable'));
        $this->assertFalse(Reflector::typeOf(1, ReflectorTest_Mock::class));

        $this->assertFalse(Reflector::typeOf(1.2, 'int'));
        $this->assertTrue(Reflector::typeOf(1.2, 'float'));
        $this->assertFalse(Reflector::typeOf(1.2, 'bool'));
        $this->assertFalse(Reflector::typeOf(1.2, 'array'));
        $this->assertFalse(Reflector::typeOf(1.2, 'string'));
        $this->assertFalse(Reflector::typeOf(1.2, 'callable'));
        $this->assertFalse(Reflector::typeOf(1.2, ReflectorTest_Mock::class));

        $this->assertFalse(Reflector::typeOf(true, 'int'));
        $this->assertFalse(Reflector::typeOf(true, 'float'));
        $this->assertTrue(Reflector::typeOf(true, 'bool'));
        $this->assertFalse(Reflector::typeOf(true, 'array'));
        $this->assertFalse(Reflector::typeOf(true, 'string'));
        $this->assertFalse(Reflector::typeOf(true, 'callable'));
        $this->assertFalse(Reflector::typeOf(true, ReflectorTest_Mock::class));

        $this->assertFalse(Reflector::typeOf([1, 2], 'int'));
        $this->assertFalse(Reflector::typeOf([1, 2], 'float'));
        $this->assertFalse(Reflector::typeOf([1, 2], 'bool'));
        $this->assertTrue(Reflector::typeOf([1, 2], 'array'));
        $this->assertFalse(Reflector::typeOf([1, 2], 'string'));
        $this->assertFalse(Reflector::typeOf([1, 2], 'callable'));
        $this->assertFalse(Reflector::typeOf([1, 2], ReflectorTest_Mock::class));

        $this->assertFalse(Reflector::typeOf('abc', 'int'));
        $this->assertFalse(Reflector::typeOf('abc', 'float'));
        $this->assertFalse(Reflector::typeOf('abc', 'bool'));
        $this->assertFalse(Reflector::typeOf('abc', 'array'));
        $this->assertTrue(Reflector::typeOf('abc', 'string'));
        $this->assertFalse(Reflector::typeOf('abc', 'callable'));
        $this->assertFalse(Reflector::typeOf('abc', ReflectorTest_Mock::class));

        $callable = fn() => 1;
        $this->assertFalse(Reflector::typeOf($callable, 'int'));
        $this->assertFalse(Reflector::typeOf($callable, 'float'));
        $this->assertFalse(Reflector::typeOf($callable, 'bool'));
        $this->assertFalse(Reflector::typeOf($callable, 'array'));
        $this->assertFalse(Reflector::typeOf($callable, 'string'));
        $this->assertTrue(Reflector::typeOf($callable, 'callable'));
        $this->assertFalse(Reflector::typeOf($callable, ReflectorTest_Mock::class));

        $object = new ReflectorTest_Mock();
        $this->assertFalse(Reflector::typeOf($object, 'int'));
        $this->assertFalse(Reflector::typeOf($object, 'float'));
        $this->assertFalse(Reflector::typeOf($object, 'bool'));
        $this->assertFalse(Reflector::typeOf($object, 'array'));
        $this->assertFalse(Reflector::typeOf($object, 'string'));
        $this->assertFalse(Reflector::typeOf($object, 'callable'));
        $this->assertTrue(Reflector::typeOf($object, ReflectorTest_Mock::class));
        $this->assertFalse(Reflector::typeOf($object, \stdClass::class));
    }

    public function test_toArgs_error(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Parameter 'mixed' is requierd.");

        $function = function ($mixed): void { return; };
        $rf       = new \ReflectionFunction($function);
        $args     = Reflector::toArgs($rf->getParameters(), []);
    }

    public function test_toArgs_errorNullable(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Parameter 'nullable' is requierd.");

        $function = function (int|null $nullable): void { return; };
        $rf       = new \ReflectionFunction($function);
        $args     = Reflector::toArgs($rf->getParameters(), []);
    }

    public function test_toArgs_errorConvert(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Parameter gender(=3) can not convert to TestApp\Enum\Gender.");

        $function = function (Gender $gender): void { return; };
        $rf       = new \ReflectionFunction($function);
        $this->assertSame([Gender::Male], Reflector::toArgs($rf->getParameters(), ['gender' => 1], true));

        Reflector::toArgs($rf->getParameters(), ['gender' => 3], true);
    }

    public function test_toArgs(): void
    {
        $function   = function (): void { return; };
        $reflection = new \ReflectionFunction($function);
        $this->assertSame([], Reflector::toArgs($reflection->getParameters(), []));

        $function   = function ($mixed, $foo = 'foo', $bar = 'bar'): void { return; };
        $reflection = new \ReflectionFunction($function);
        $this->assertSame([[], 'foo', 'BAR'], Reflector::toArgs($reflection->getParameters(), [[], 'bar' => 'BAR']));

        $function   = function (string ...$variadic): void { return; };
        $reflection = new \ReflectionFunction($function);
        $this->assertSame([], Reflector::toArgs($reflection->getParameters(), []));

        $function   = function (string $optional = 'default'): void { return; };
        $reflection = new \ReflectionFunction($function);
        $this->assertSame(['default'], Reflector::toArgs($reflection->getParameters(), []));

        $function   = function ($mixed, string $string, string|null $nullable, string $optional = 'default', string ...$variadic): void { return; };
        $reflection = new \ReflectionFunction($function);
        $this->assertSame(
            ['a', 'b', 'c', 'd', 'e'],
            Reflector::toArgs($reflection->getParameters(), [
                'mixed'    => 'a',
                'string'   => 'b',
                'nullable' => 'c',
                'optional' => 'd',
                'variadic' => 'e',
            ]),
        );
        $this->assertSame(
            ['a', 'b', 'c', 'd', 'e'],
            Reflector::toArgs($reflection->getParameters(), [
                'nullable' => 'c',
                'string'   => 'b',
                'mixed'    => 'a',
                'optional' => 'd',
                'variadic' => 'e',
            ]),
        );
        $this->assertSame(
            ['a', 'b', null, 'default', 'e', 'f', 'g'],
            Reflector::toArgs($reflection->getParameters(), [
                'mixed'    => 'a',
                'string'   => 'b',
                'nullable' => null,
                'variadic' => ['e', 'f', 'g'],
            ]),
        );
        $this->assertSame(
            ['a', 'b', 'c', 'd', 'e'],
            Reflector::toArgs($reflection->getParameters(), ['a', 'b', 'c', 'd', 'e']),
        );
        $this->assertSame(
            ['a', 'b', 'c', 'd', 'e', 'f'],
            Reflector::toArgs($reflection->getParameters(), ['a', 'b', 'c', 'd', 'e', 'f']),
        );
        $this->assertSame(
            ['a', 'b', 'c', 'd', ['e', 'f']],
            Reflector::toArgs($reflection->getParameters(), ['a', 'b', 'c', 'd', ['e', 'f']]),
        );

        $function   = function (int $convert): void { return; };
        $reflection = new \ReflectionFunction($function);
        $this->assertSame(['1'], Reflector::toArgs($reflection->getParameters(), ['convert' => '1']));
        $this->assertSame([1], Reflector::toArgs($reflection->getParameters(), ['convert' => '1'], true));

        $function   = function (int ...$convert): void { return; };
        $reflection = new \ReflectionFunction($function);
        $this->assertSame(['1', '2', '3'], Reflector::toArgs($reflection->getParameters(), ['convert' => ['1', '2', '3']]));
        $this->assertSame([1, 2, 3], Reflector::toArgs($reflection->getParameters(), ['convert' => ['1', '2', '3']], true));
    }

    public function test_toNamedArgs(): void
    {
        $function   = function (): void { return; };
        $reflection = new \ReflectionFunction($function);
        $this->assertSame([], Reflector::toNamedArgs($reflection->getParameters(), []));
        $this->assertSame([], Reflector::toNamedArgs($reflection->getParameters(), [1, 2, 3]));

        $function   = function (string ...$variadic): void { return; };
        $reflection = new \ReflectionFunction($function);
        $this->assertSame([], Reflector::toNamedArgs($reflection->getParameters(), []));
        $this->assertSame(['variadic' => [1]], Reflector::toNamedArgs($reflection->getParameters(), [1]));
        $this->assertSame(['variadic' => [1, 2, 3]], Reflector::toNamedArgs($reflection->getParameters(), [1, 2, 3]));
        $this->assertSame(['variadic' => [[1, 2, 3]]], Reflector::toNamedArgs($reflection->getParameters(), [[1, 2, 3]]));
        $this->assertSame(['variadic' => [1, 'a' => 2, 'b' => 3]], Reflector::toNamedArgs($reflection->getParameters(), [1, 'a' => 2, 'b' => 3]));

        $function   = function ($foo, $bar = 'bar', string ...$variadic): void { return; };
        $reflection = new \ReflectionFunction($function);
        $this->assertSame(['foo' => 1], Reflector::toNamedArgs($reflection->getParameters(), [1]));
        $this->assertSame(['foo' => 1, 'bar' => 2, 'variadic' => [3, 4]], Reflector::toNamedArgs($reflection->getParameters(), [1, 2, 3, 4]));
        $this->assertSame(['foo' => 4, 'bar' => 3, 'variadic' => ['qux' => 1, 'baz' => 2]], Reflector::toNamedArgs($reflection->getParameters(), ['qux' => 1, 'baz' => 2, 'bar' => 3, 'foo' => 4]));

        $function   = function (string $optional = 'default'): void { return; };
        $reflection = new \ReflectionFunction($function);
        $this->assertSame([], Reflector::toNamedArgs($reflection->getParameters(), []));
        $this->assertSame(['optional' => 1], Reflector::toNamedArgs($reflection->getParameters(), [1, 2, 3]));

        $function   = function ($mixed, string $string, string|null $nullable, string $optional = 'default', string ...$variadic): void { return; };
        $reflection = new \ReflectionFunction($function);
        $this->assertSame(
            [
                'mixed' => 'a',
            ],
            Reflector::toNamedArgs($reflection->getParameters(), ['a']),
        );
        $this->assertSame(
            [
                'mixed'    => 'a',
                'string'   => 'b',
                'nullable' => 'c',
            ],
            Reflector::toNamedArgs($reflection->getParameters(), ['a', 'b', 'c']),
        );
        $this->assertSame(
            [
                'mixed'    => 'a',
                'string'   => 'b',
                'nullable' => 'c',
                'optional' => 'd',
                'variadic' => ['e'],
            ],
            Reflector::toNamedArgs($reflection->getParameters(), ['a', 'b', 'c', 'd', 'e']),
        );
        $this->assertSame(
            [
                'mixed'    => 'a',
                'string'   => null,
                'nullable' => null,
                'optional' => 'd',
                'variadic' => ['e', 'f', 'g'],
            ],
            Reflector::toNamedArgs($reflection->getParameters(), ['a', null, null, 'd', 'e', 'f', 'g']),
        );
        $this->assertSame(
            [
                'mixed'    => 'a',
                'string'   => 'b',
                'nullable' => 'c',
                'variadic' => ['e', 'f'],
            ],
            Reflector::toNamedArgs($reflection->getParameters(), ['a', 'b', 'c', 'variadic' => ['e', 'f']]),
        );
    }

    public function test_toNamedArgs_invalidArgsOrder(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Named arguments must come after positional arguments.");

        $function   = function (): void { return; };
        $reflection = new \ReflectionFunction($function);
        Reflector::toNamedArgs($reflection->getParameters(), [1, 'a' => 2, 3]);
    }

    public function test_toNamedArgs_duplicateArgsPositionalAndNamed(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Named arguments of 'foo' duplicate to 1st positional arguments.");

        $function   = function ($foo): void { return; };
        $reflection = new \ReflectionFunction($function);
        Reflector::toNamedArgs($reflection->getParameters(), [1, 'foo' => 2]);
    }

    public function test_mergeArgs(): void
    {
        $function   = function ($mixed, string $string, string|null $nullable, string $optional = 'default', string ...$variadic): void { return; };
        $reflection = new \ReflectionFunction($function);
        $parameters = $reflection->getParameters();
        $this->assertSame([], Reflector::mergeArgs($parameters, [], []));
        $this->assertSame(['mixed' => 1], Reflector::mergeArgs($parameters, [1], []));
        $this->assertSame(['mixed' => 1], Reflector::mergeArgs($parameters, [], [1]));
        $this->assertSame(
            [
                'mixed'    => 'A',
                'string'   => 'B',
                'nullable' => 'c',
            ],
            Reflector::mergeArgs($parameters, ['a', 'b', 'c'], ['A', 'B']),
        );
        $this->assertSame(
            [
                'mixed'    => 'A',
                'string'   => 'B',
                'nullable' => 'C',
            ],
            Reflector::mergeArgs($parameters, ['a', 'b'], ['A', 'B', 'C']),
        );
        $this->assertSame(
            [
                'mixed'    => 'a',
                'string'   => 'B',
                'nullable' => 'c',
            ],
            Reflector::mergeArgs($parameters, ['a', 'b', 'c'], ['string' => 'B']),
        );
        $this->assertSame(
            [
                'string' => 'b',
                'mixed'  => 'A',
            ],
            Reflector::mergeArgs($parameters, ['string' => 'b'], ['A']),
        );
        $this->assertSame(
            [
                'string'   => 'B',
                'mixed'    => 'A',
                'nullable' => 'C',
            ],
            Reflector::mergeArgs($parameters, ['string' => 'b'], ['A', 'B', 'C']),
        );
        $this->assertSame(
            [
                'string' => 'B',
            ],
            Reflector::mergeArgs($parameters, ['string' => 'b'], ['string' => 'B']),
        );
        $this->assertSame(
            [
                'string'   => 'B',
                'nullable' => 'c',
                'optional' => 'D',
            ],
            Reflector::mergeArgs($parameters, ['string' => 'b', 'nullable' => 'c'], ['string' => 'B', 'optional' => 'D']),
        );
        $this->assertSame(
            [
                'string'   => 2,
                'mixed'    => 1,
                'nullable' => 3,
                'optional' => 4,
                'variadic' => [5, 6, 7],
            ],
            Reflector::mergeArgs($parameters, ['string' => 'b'], [1, 2, 3, 4, 5, 6, 7]),
        );
        $this->assertSame(
            [
                'mixed'    => 1,
                'string'   => 'B',
                'nullable' => 3,
                'optional' => 4,
                'variadic' => [5, 6, 7],
            ],
            Reflector::mergeArgs($parameters, [1, 2, 3, 4, 5, 6, 7], ['string' => 'B']),
        );
        $this->assertSame(
            [
                'mixed'    => 1,
                'string'   => 'B',
                'nullable' => 3,
                'optional' => 4,
                'variadic' => ['E', 'F'],
            ],
            Reflector::mergeArgs($parameters, [1, 2, 3, 4, 5, 6, 7], ['string' => 'B', 'variadic' => ['E', 'F']]),
        );
    }

    public function test_convert_array(): void
    {
        $type = 'array';
        $this->assertNull(Reflector::convert(null, $type));

        $this->assertSame([], Reflector::convert([], $type));
        $this->assertSame([1, 2], Reflector::convert([1, 2], $type));
        $this->assertSame(['a' => 'A'], Reflector::convert(['a' => 'A'], $type));

        $this->assertSame([''], Reflector::convert('', $type));
        $this->assertSame(['a'], Reflector::convert('a', $type));
        $this->assertSame(['a,b,c'], Reflector::convert('a,b,c', $type));

        $to_array = new ToArrayStub([1, 2, 'a' => 'A']);
        $this->assertSame([1, 2, 'a' => 'A'], Reflector::convert($to_array, $type));

        $travers = new \ArrayObject([1, 2, 'a' => 'A']);
        $this->assertSame([1, 2, 'a' => 'A'], Reflector::convert($travers, $type));

        $jsonValue = Gender::Male;
        $this->assertSame([$jsonValue], Reflector::convert($jsonValue, $type));
        $jsonValue = new JsonSerializableStub('abc');
        $this->assertSame([$jsonValue], Reflector::convert($jsonValue, $type));

        $jsonArray = new JsonSerializableStub([1, 2, 'a' => 'A']);
        $this->assertSame([1, 2, 'a' => 'A'], Reflector::convert($jsonArray, $type));

        $this->assertSame([1], Reflector::convert(1, $type));
        $this->assertSame([1.2], Reflector::convert(1.2, $type));
        $this->assertSame([true], Reflector::convert(true, $type));

        $resource = fopen('vfs://root/dummy.txt', 'r');
        $this->assertSame([$resource], Reflector::convert($resource, $type));
        fclose($resource);
    }

    public function test_convert_string(): void
    {
        $type = 'string';
        $this->assertNull(Reflector::convert(null, $type));

        $this->assertSame('', Reflector::convert('', $type));
        $this->assertSame('a', Reflector::convert('a', $type));
        $this->assertSame('a,b,c', Reflector::convert('a,b,c', $type));

        $resource = fopen('vfs://root/dummy.txt', 'r');
        $this->assertSame(null, Reflector::convert($resource, $type));
        fclose($resource);

        $this->assertSame('1', Reflector::convert(1, $type));
        $this->assertSame('1.2', Reflector::convert(1.2, $type));
        $this->assertSame('1200', Reflector::convert(1.2e3, $type));
        $this->assertSame('1', Reflector::convert(true, $type));
        $this->assertSame('', Reflector::convert(false, $type));
        $this->assertSame('abc', Reflector::convert('abc', $type));

        // The Enumeration is converted to the string of the value (not the label, because it is the same as the PHP enum and the JSON, and it does not depend on the locale)
        $this->assertSame('1', Reflector::convert(Gender::Male, $type));
        $this->assertSame('3', Reflector::convert(GroupPosition::Member, $type));
        $jsonValue = new JsonSerializableStub('abc');
        $this->assertSame('abc', Reflector::convert($jsonValue, $type));

        $this->assertSame('[1,2]', Reflector::convert([1, 2], $type));
        $this->assertSame('{"a":"A"}', Reflector::convert(['a' => 'A'], $type));
        $this->assertSame('[1,2]', Reflector::convert(new \ArrayObject([1, 2]), $type));
        $this->assertSame('{"a":"A"}', Reflector::convert(new \ArrayObject(['a' => 'A']), $type));

        $jsonArray = new JsonSerializableStub([1, 2]);
        $this->assertSame('[1,2]', Reflector::convert($jsonArray, $type));
        $toString = new ReflectorTest_Mock();
        $this->assertSame('default', Reflector::convert($toString, $type));

        $this->assertSame(null, Reflector::convert(new ToArrayStub([1, 2]), $type));
    }

    public function test_convert_callable(): void
    {
        $type = 'callable';
        $this->assertNull(Reflector::convert(null, $type));

        $closure = fn() => 123;
        $this->assertSame($closure, Reflector::convert($closure, $type));

        $array = [$this, 'test_convert_callable'];
        $this->assertSame([$this, 'test_convert_callable'], Reflector::convert([$this, 'test_convert_callable'], $type));

        $invoke = new ReflectorTest_Invoke();
        $this->assertSame($invoke, Reflector::convert($invoke, $type));

        $not_invoke = new ReflectorTest_Mock();
        $this->assertSame(null, Reflector::convert($not_invoke, $type));
        $this->assertSame(null, Reflector::convert(1, $type));
        $this->assertSame(null, Reflector::convert('1', $type));
    }

    public function test_convert_closure(): void
    {
        $type = \Closure::class;
        $this->assertNull(Reflector::convert(null, $type));

        $closure = fn() => 123;
        $this->assertSame($closure, Reflector::convert($closure, $type));

        $array        = [$this, 'test_convert_callable'];
        $arrayClosure = \Closure::fromCallable($array);
        $this->assertEquals($arrayClosure, Reflector::convert([$this, 'test_convert_callable'], $type));

        $invoke        = new ReflectorTest_Invoke();
        $invokeClosure = \Closure::fromCallable($invoke);
        $this->assertEquals($invokeClosure, Reflector::convert($invoke, $type));

        $not_invoke = new ReflectorTest_Mock();
        $this->assertSame(null, Reflector::convert($not_invoke, $type));
        $this->assertSame(null, Reflector::convert(1, $type));
        $this->assertSame(null, Reflector::convert('1', $type));
    }

    public function test_convert_int(): void
    {
        $type = 'int';
        $this->assertNull(Reflector::convert(null, $type));
        $this->assertNull(Reflector::convert('', $type));
        $this->assertNull(Reflector::convert([], $type));

        $this->assertSame(0, Reflector::convert(0, $type));
        $this->assertSame(1, Reflector::convert(1, $type));
        $this->assertSame(1, Reflector::convert(1.2, $type));
        $this->assertSame(2, Reflector::convert(2.9, $type));
        $this->assertSame(1, Reflector::convert('1', $type));
        $this->assertSame(1, Reflector::convert('1.2', $type));
        $this->assertSame(2, Reflector::convert('2.9', $type));
        $this->assertSame(0, Reflector::convert('a', $type));

        $this->assertSame(null, Reflector::convert([1, 2], $type));

        $resource = fopen('vfs://root/dummy.txt', 'r');
        $this->assertSame(null, Reflector::convert($resource, $type));
        fclose($resource);

        $object = new ReflectorTest_Mock();
        $this->assertSame(null, Reflector::convert($object, $type));

        $convertTo = new ReflectorTest_ConvertTo();
        $this->assertSame(123, Reflector::convert($convertTo, $type));

        $toType = new ReflectorTest_ToType();
        $this->assertSame(123, Reflector::convert($toType, $type));
    }

    public function test_convert_float(): void
    {
        $type = 'float';
        $this->assertNull(Reflector::convert(null, $type));
        $this->assertNull(Reflector::convert('', $type));
        $this->assertNull(Reflector::convert([], $type));

        $this->assertSame(0.0, Reflector::convert(0, $type));
        $this->assertSame(1.0, Reflector::convert(1, $type));
        $this->assertSame(1.2, Reflector::convert(1.2, $type));
        $this->assertSame(2.9, Reflector::convert(2.9, $type));
        $this->assertSame(1.0, Reflector::convert('1', $type));
        $this->assertSame(1.2, Reflector::convert('1.2', $type));
        $this->assertSame(2.9, Reflector::convert('2.9', $type));
        $this->assertSame(0.0, Reflector::convert('a', $type));

        $this->assertSame(null, Reflector::convert([1, 2], $type));

        $resource = fopen('vfs://root/dummy.txt', 'r');
        $this->assertSame(null, Reflector::convert($resource, $type));
        fclose($resource);

        $object = new ReflectorTest_Mock();
        $this->assertSame(null, Reflector::convert($object, $type));

        $convertTo = new ReflectorTest_ConvertTo();
        $this->assertSame(4.56, Reflector::convert($convertTo, $type));

        $toType = new ReflectorTest_ToType();
        $this->assertSame(1.23, Reflector::convert($toType, $type));
    }

    public function test_convert_bool(): void
    {
        $type = 'bool';
        $this->assertNull(Reflector::convert(null, $type));
        $this->assertNull(Reflector::convert('', $type));
        $this->assertNull(Reflector::convert([], $type));

        $this->assertSame(true, Reflector::convert(true, $type));
        $this->assertSame(true, Reflector::convert(1, $type));
        $this->assertSame(true, Reflector::convert(1.2, $type));
        $this->assertSame(true, Reflector::convert(2.9, $type));
        $this->assertSame(true, Reflector::convert('1', $type));
        $this->assertSame(true, Reflector::convert('1.2', $type));
        $this->assertSame(true, Reflector::convert('2.9', $type));
        $this->assertSame(true, Reflector::convert('a', $type));
        $this->assertSame(false, Reflector::convert(0, $type));
        $this->assertSame(false, Reflector::convert(false, $type));

        $this->assertSame(null, Reflector::convert([1, 2], $type));

        $resource = fopen('vfs://root/dummy.txt', 'r');
        $this->assertSame(null, Reflector::convert($resource, $type));
        fclose($resource);

        $object = new ReflectorTest_Mock();
        $this->assertSame(null, Reflector::convert($object, $type));

        $convertTo = new ReflectorTest_ConvertTo();
        $this->assertSame(false, Reflector::convert($convertTo, $type));

        $toType = new ReflectorTest_ToType();
        $this->assertSame(true, Reflector::convert($toType, $type));
    }

    public function test_convert_object(): void
    {
        $this->assertNull(Reflector::convert(null, Gender::class));
        $this->assertSame(Gender::Male, Reflector::convert(1, Gender::class));
        $this->assertSame(Gender::Female, Reflector::convert(2, Gender::class));

        $object = new ReflectorTest_Mock();
        $this->assertSame(null, Reflector::convert($object, ReflectorTest_ConvertTo::class));

        $convertTo = new ReflectorTest_ConvertTo();
        $this->assertEquals(new ReflectorTest_ToType(), Reflector::convert($convertTo, ReflectorTest_ToType::class));

        $toType = new ReflectorTest_ToType();
        $this->assertEquals(new ReflectorTest_ConvertTo(), Reflector::convert($toType, ReflectorTest_ConvertTo::class));

        $this->assertEquals(null, Reflector::convert($toType, Gender::class));
    }

    public function test_convert_enum_backedInt(): void
    {
        $type = ReflectorTest_IntEnum::class;
        $this->assertNull(Reflector::convert(null, $type));

        // same type (no convert)
        $this->assertSame(ReflectorTest_IntEnum::One, Reflector::convert(ReflectorTest_IntEnum::One, $type));

        // int value
        $this->assertSame(ReflectorTest_IntEnum::One, Reflector::convert(1, $type));
        $this->assertSame(ReflectorTest_IntEnum::Zero, Reflector::convert(0, $type));

        // string value (e.g. request parameter) is converted to int
        $this->assertSame(ReflectorTest_IntEnum::One, Reflector::convert('1', $type));
        $this->assertSame(ReflectorTest_IntEnum::Zero, Reflector::convert('0', $type));

        // integral float
        $this->assertSame(ReflectorTest_IntEnum::One, Reflector::convert(1.0, $type));

        // can not convert
        $this->assertNull(Reflector::convert(9, $type));
        $this->assertNull(Reflector::convert('9', $type));
        $this->assertNull(Reflector::convert(1.5, $type));
        $this->assertNull(Reflector::convert('one', $type));
        $this->assertNull(Reflector::convert('', $type));
        $this->assertNull(Reflector::convert(true, $type));
        $this->assertNull(Reflector::convert([1], $type));
    }

    public function test_convert_enum_backedString(): void
    {
        $type = ReflectorTest_StringEnum::class;
        $this->assertNull(Reflector::convert(null, $type));

        $this->assertSame(ReflectorTest_StringEnum::A, Reflector::convert('a', $type));
        $this->assertSame(ReflectorTest_StringEnum::Num, Reflector::convert('1', $type));

        // int value is converted to string
        $this->assertSame(ReflectorTest_StringEnum::Num, Reflector::convert(1, $type));

        // can not convert
        $this->assertNull(Reflector::convert('zz', $type));
        $this->assertNull(Reflector::convert(2, $type));
        $this->assertNull(Reflector::convert(true, $type));
        $this->assertNull(Reflector::convert(['a'], $type));
    }

    public function test_convert_enum_pure(): void
    {
        $type = ReflectorTest_PureEnum::class;
        $this->assertNull(Reflector::convert(null, $type));

        // pure enum is converted by case name
        $this->assertSame(ReflectorTest_PureEnum::Alpha, Reflector::convert('Alpha', $type));
        $this->assertSame(ReflectorTest_PureEnum::Beta, Reflector::convert('Beta', $type));

        // can not convert (case sensitive)
        $this->assertNull(Reflector::convert('alpha', $type));
        $this->assertNull(Reflector::convert('Gamma', $type));
        $this->assertNull(Reflector::convert(1, $type));
        $this->assertNull(Reflector::convert(true, $type));
        $this->assertNull(Reflector::convert(['Alpha'], $type));
    }

    public function test_convert_enum_convertible(): void
    {
        // Convertible enum is converted by valueOf() (not by tryFrom())
        $type = ReflectorTest_ConvertibleEnum::class;
        $this->assertSame(ReflectorTest_ConvertibleEnum::A, Reflector::convert('one', $type));
        $this->assertNull(Reflector::convert(1, $type));
        $this->assertNull(Reflector::convert('1', $type));
    }

    public function test_convert_not(): void
    {
        $this->assertNull(Reflector::convert(null, null));
        $this->assertSame(123, Reflector::convert(123, null));
        $this->assertSame([1, 2], Reflector::convert([1, 2], null));
    }

    public function test_getTypeHint(): void
    {
        $closure = function ($none, int $int, string $string, callable $callable, \Closure $closure, ReflectorTest_Mock $mock): void {};
        $rf      = new \ReflectionFunction($closure);
        $params  = $rf->getParameters();
        $this->assertSame(null, Reflector::getTypeHint(null));
        $this->assertSame(null, Reflector::getTypeHint($params[0]));
        $this->assertSame('int', Reflector::getTypeHint($params[1]));
        $this->assertSame('string', Reflector::getTypeHint($params[2]));
        $this->assertSame('callable', Reflector::getTypeHint($params[3]));
        $this->assertSame(\Closure::class, Reflector::getTypeHint($params[4]));
        $this->assertSame(ReflectorTest_Mock::class, Reflector::getTypeHint($params[5]));
    }

    public function test_getParameterTypeHintOf(): void
    {
        $closure = function ($none, int $int, string $string, callable $callable, \Closure $closure, ReflectorTest_Mock $mock): void {};
        $this->assertSame(null, Reflector::getParameterTypeHintOf(null, 0));
        $this->assertSame(null, Reflector::getParameterTypeHintOf($closure, 0));
        $this->assertSame('int', Reflector::getParameterTypeHintOf($closure, 1));
        $this->assertSame('string', Reflector::getParameterTypeHintOf($closure, 2));
        $this->assertSame('callable', Reflector::getParameterTypeHintOf($closure, 3));
        $this->assertSame(\Closure::class, Reflector::getParameterTypeHintOf($closure, 4));
        $this->assertSame(ReflectorTest_Mock::class, Reflector::getParameterTypeHintOf($closure, 5));
        $this->assertSame(null, Reflector::getParameterTypeHintOf($closure, 6));
    }

    public function test_getPropertyTypeHintOf(): void
    {
        $class = new class {
            public $none;
            public int $int;
            public string $string;
            public \Closure $class;
            public ReflectorTest_Mock $mock;
        };
        $this->assertSame(null, Reflector::getPropertyTypeHintOf(null, 'int'));
        $this->assertSame(null, Reflector::getPropertyTypeHintOf($class, 'none'));
        $this->assertSame('int', Reflector::getPropertyTypeHintOf($class, 'int'));
        $this->assertSame('string', Reflector::getPropertyTypeHintOf($class, 'string'));
        $this->assertSame(\Closure::class, Reflector::getPropertyTypeHintOf($class, 'class'));
        $this->assertSame(ReflectorTest_Mock::class, Reflector::getPropertyTypeHintOf($class, 'mock'));
        $this->assertSame(null, Reflector::getPropertyTypeHintOf($class, 'nothing'));
    }

    public function test_uses(): void
    {
        $parent = new ReflectorTest_TraitParent();
        $child  = new ReflectorTest_TraitChild();
        $this->assertTrue(Reflector::uses($parent, Describable::class));
        $this->assertTrue(Reflector::uses($child, Describable::class));
        $this->assertTrue(Reflector::uses(ReflectorTest_TraitParent::class, Describable::class));
        $this->assertTrue(Reflector::uses(ReflectorTest_TraitChild::class, Describable::class));

        $this->assertFalse(Reflector::uses($parent, Configurable::class));
        $this->assertFalse(Reflector::uses($child, Configurable::class));
        $this->assertFalse(Reflector::uses(ReflectorTest_TraitParent::class, Configurable::class));
        $this->assertFalse(Reflector::uses(ReflectorTest_TraitChild::class, Configurable::class));
    }

    public function test_get_edgeCases(): void
    {
        // null object returns the default
        $this->assertSame('default', Reflector::get(null, 'name', 'default'));
        $this->assertNull(Reflector::get(null, 'name'));

        // the parent of the nested key is null then the default is returned
        $this->assertSame('default', Reflector::get($this->map, 'children.name', 'default'));
        $this->assertSame('default', Reflector::get($this->object, 'children.name', 'default'));
        $this->assertSame('default', Reflector::get($this->map, 'nothing.name', 'default'));

        // the null value is the default
        $this->assertSame('default', Reflector::get($this->map, 'children', 'default'));
        $this->assertSame('default', Reflector::get($this->object, 'children', 'default'));
        $this->assertSame('default', Reflector::get($this->array, 3, 'default'));

        // blank key returns the object itself (DotAccessDelegator is resolved)
        $this->assertSame($this->map, Reflector::get($this->map, null));
        $this->assertSame($this->map, Reflector::get($this->map, ''));
        $this->assertSame(['b' => 'ab', 'c' => 'ac'], Reflector::get($this->transparent->a, null));

        // the top level DotAccessDelegator is resolved
        $this->assertSame('ab', Reflector::get($this->transparent->a, 'b'));
        $this->assertSame('ac', Reflector::get($this->transparent->a, 'c'));
        $this->assertSame('default', Reflector::get($this->transparent->b, 'nothing', 'default'));

        // the integer key and the numeric string key
        $this->assertSame('b', Reflector::get($this->array, 1));
        $this->assertSame('b', Reflector::get($this->array, '1'));
        $this->assertSame('outdoor', Reflector::get($this->map, 'hobbies.1'));
        $this->assertSame('outdoor', Reflector::get($this->object, 'hobbies.1'));

        // the static property via the class name
        // (the static values may be changed by the other tests, so that they are compared with the current values)
        $this->assertSame(ReflectorTest_Accessible::$static_public, Reflector::get(ReflectorTest_Accessible::class, 'static_public'));
        $this->assertNull(Reflector::get(ReflectorTest_Accessible::class, 'static_private'));
        $this->assertSame((new \ReflectionProperty(ReflectorTest_Accessible::class, 'static_private'))->getValue(), Reflector::get(ReflectorTest_Accessible::class, 'static_private', null, true));
    }

    public function test_has_edgeCases(): void
    {
        $this->assertFalse(Reflector::has(null, 'name'));

        // the top level DotAccessDelegator is resolved
        $this->assertTrue(Reflector::has($this->transparent->a, 'b'));
        $this->assertTrue(Reflector::has($this->transparent->a, 'c'));
        $this->assertFalse(Reflector::has($this->transparent->a, 'nothing'));

        // the null value exists
        $this->assertTrue(Reflector::has($this->map, 'children'));
        $this->assertTrue(Reflector::has($this->object, 'children'));
        $this->assertTrue(Reflector::has($this->array, 3));

        // the nested key on the null value does not exist
        $this->assertFalse(Reflector::has($this->map, 'children.name'));
        $this->assertFalse(Reflector::has($this->object, 'children.name'));

        // the static property via the class name
        $this->assertTrue(Reflector::has(ReflectorTest_Accessible::class, 'static_public'));
        $this->assertFalse(Reflector::has(ReflectorTest_Accessible::class, 'static_private'));
        $this->assertTrue(Reflector::has(ReflectorTest_Accessible::class, 'static_private', true));
    }

    public function test_remove_nestedParentKeyNotExists(): void
    {
        try {
            Reflector::remove($this->map, 'nothing.name');
            $this->fail('Must not execute.');
        } catch (\OutOfBoundsException $e) {
            $this->assertSame("Nested parent key 'nothing' does not exist.", $e->getMessage());
        }

        try {
            Reflector::remove($this->object, 'nothing.name');
            $this->fail('Must not execute.');
        } catch (\OutOfBoundsException $e) {
            $this->assertSame("Nested key 'nothing' does not exist.", $e->getMessage());
        }

        try {
            Reflector::remove($this->object, 'nothing');
            $this->fail('Must not execute.');
        } catch (\OutOfBoundsException $e) {
            $this->assertSame("Nested key 'nothing' does not exist.", $e->getMessage());
        }

        // The array (map) does not throw the exception for the terminal key (it returns null)
        $this->assertNull(Reflector::remove($this->map, 'nothing'));
    }

    public function test_convert_stdClass(): void
    {
        $converted = Reflector::convert(['a' => 1, 'b' => 'B'], 'stdClass');
        $this->assertInstanceOf(\stdClass::class, $converted);
        $this->assertEquals((object) ['a' => 1, 'b' => 'B'], $converted);

        $object = new \stdClass();
        $this->assertSame($object, Reflector::convert($object, 'stdClass'));
        $this->assertNull(Reflector::convert(null, 'stdClass'));
    }

    public function test_convert_exceptionIsNull(): void
    {
        // The exception thrown while converting is converted to null
        $this->assertNull(Reflector::convert(new ReflectorTest_ThrowToString(), 'string'));
        $this->assertNull(Reflector::convert('foo', ReflectorTest_ThrowValueOf::class));
        $this->assertNull(Reflector::convert(new ReflectorTest_ThrowConvertTo(), ReflectorTest_Mock::class));
    }

    public function test_getType(): void
    {
        $this->assertNull(Reflector::getType(null));
        $this->assertSame(ReflectorTest_Mock::class, Reflector::getType(new ReflectorTest_Mock()));
        $this->assertSame(\Closure::class, Reflector::getType(fn() => 1));
        $this->assertSame('string', Reflector::getType('foo'));
        $this->assertSame('string', Reflector::getType('strlen')); // string has priority over callable
        $this->assertSame('array', Reflector::getType([1, 2]));
        $this->assertSame('int', Reflector::getType(1));
        $this->assertSame('float', Reflector::getType(1.5));
        $this->assertSame('bool', Reflector::getType(false));
        $resource = fopen('php://memory', 'r');
        try {
            $this->assertSame('resource', Reflector::getType($resource));
        } finally {
            fclose($resource);
        }

        // The closed resource is not any type
        $this->assertNull(Reflector::getType($resource));
    }

    public function test_instantiate_closureAndNonStringFactory(): void
    {
        // Closure is evaluated
        $this->assertSame('closure', Reflector::instantiate(fn() => new ReflectorTest_Mock('closure'))->value);

        // The callable factory is evaluated with the arguments
        $this->assertSame('callable arg', Reflector::instantiate([
            '@factory' => fn($value) => new ReflectorTest_Mock("callable {$value}"),
            'value'    => 'arg',
        ])->value);

        // The object factory (not callable) is returned as it is, and the setup is applied
        $mock = new ReflectorTest_Mock('object');
        $this->assertSame('object setup', Reflector::instantiate([
            '@factory' => $mock,
            '@setup'   => function ($m) {
                $m->value .= ' setup';
                return $m;
            },
        ])->value);
    }

    public function test_instantiate_withoutFactory(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Unable to instantiate, because of '@factory' or zero index valule is undefined.");
        Reflector::instantiate(['value' => 'arg']);
    }

    public function test_caller(): void
    {
        $this->assertEquals('test_caller', $this->colledFromTestCaller());
    }

    private function colledFromTestCaller()
    {
        return Reflector::caller();
    }
}

class ReflectorTest_Mock
{
    public $value = null;

    public function __construct($value = 'default')
    {
        $this->value = $value;
    }

    public static function getInstance()
    {
        return new static('via getInstance()');
    }

    public static function build($value)
    {
        return new static($value . ' via build()');
    }

    public function __toString()
    {
        return (string) $this->value;
    }
}
class ReflectorTest_ValueOf
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public static function valueOf($value)
    {
        return new static($value);
    }
}
class ReflectorTest_ConvertTo
{
    public function convertTo($type)
    {
        switch ($type) {
            case 'int':
                return 123;
            case 'float':
                return 4.56;
            case 'bool':
                return false;
            case ReflectorTest_ToType::class:
                return new ReflectorTest_ToType();
        }
        return null;
    }
}
class ReflectorTest_ToType
{
    public function toInt()
    {
        return 123;
    }

    public function toFloat()
    {
        return 1.23;
    }

    public function toBool()
    {
        return true;
    }

    public function toReflectorTest_ConvertTo()
    {
        return new ReflectorTest_ConvertTo();
    }

    public function toGender()
    {
        return 'Other Type';
    }
}
class ReflectorTest_Invoke
{
    public function __invoke()
    {
        return 123;
    }
}
#[\AllowDynamicProperties]
class ReflectorTest_Accessible
{
    private static $static_private     = 'static_private';
    protected static $static_protected = 'static_protected';
    public static $static_public       = 'static_public';

    private $private     = 'private';
    protected $protected = 'protected';
    public $public       = 'public';

    private $private_parent = null;
    public $public_parent   = null;

    public function __construct()
    {
        $this->dinamic = 'dinamic';
    }

    public function setPrivateParent($private_parent): void
    {
        $this->private_parent = $private_parent;
    }

    private function callPrivate()
    {
        return 'private';
    }

    protected function callProtected()
    {
        return 'protected';
    }

    public function callPublic()
    {
        return 'public';
    }

    private static function callStaticPrivate()
    {
        return 'private';
    }

    protected static function callStaticProtected()
    {
        return 'protected';
    }

    public static function callStaticPublic()
    {
        return 'public';
    }

    public function callPublicWithArgs($arg)
    {
        return 'public - ' . $arg;
    }

    public function callPublicWithTypeHintingArgs(Gender $gender, int $age)
    {
        return "public - {$age} years old {$gender->label()}";
    }
}
class ReflectorTest_AccessibleChild extends ReflectorTest_Accessible {}
class ReflectorTest_ArrayAccessible implements \ArrayAccess, \Countable, \IteratorAggregate
{
    private $private     = 'private';
    protected $protected = 'protected';
    public $public       = 'public';

    private array $items = [];

    #[Override]
    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    #[Override]
    public function offsetGet($offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    #[Override]
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    #[Override]
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    #[Override]
    public function count(): int
    {
        return count($this->items);
    }

    #[Override]
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->items);
    }
}
class ReflectorTest_TraitParent
{
    use Describable;
}
class ReflectorTest_TraitChild extends ReflectorTest_TraitParent {}
class ReflectorTest_CreateDefault
{
    public $foo;
    public $bar;

    public function __construct(string $foo = 'Foo', string $bar = 'Bar')
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}
class ReflectorTest_CreateExtends extends ReflectorTest_CreateDefault
{
    // No definistion
}

enum ReflectorTest_IntEnum: int
{
    case One  = 1;
    case Zero = 0;
}

enum ReflectorTest_StringEnum: string
{
    case A   = 'a';
    case Num = '1';
}

enum ReflectorTest_PureEnum
{
    case Alpha;
    case Beta;
}

enum ReflectorTest_ConvertibleEnum: int implements Convertible
{
    case A = 1;

    #[Override]
    public static function valueOf($value)
    {
        return $value === 'one' ? self::A : null ;
    }

    #[Override]
    public function convertTo(string $type)
    {
        return null;
    }
}

class ReflectorTest_ThrowToString
{
    public function __toString(): string
    {
        throw new \RuntimeException('to string failed');
    }
}

class ReflectorTest_ThrowValueOf
{
    public static function valueOf($value): static
    {
        throw new \RuntimeException('valueOf failed');
    }
}

class ReflectorTest_ThrowConvertTo
{
    public function convertTo(string $type)
    {
        throw new \RuntimeException('convertTo failed');
    }
}
