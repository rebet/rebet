<?php
namespace Rebet\Tests\Validation;

use Rebet\Foundation\App;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\RebetTestCase;
use Rebet\Validation\Context;
use Rebet\Validation\Valid;

class ContextTest extends RebetTestCase
{
    private $errors;
    private $rule_set;

    public function setup()
    {
        parent::setUp();
        $this->errors   = [];
        $this->rule_set = [
            'name' => [
                'label' => '氏名',
                'rule'  => [
                    ['CU', Valid::REQUIRED],
                ]
            ],
            'name_withMessage' => [
                'label' => '氏名',
                'rule'  => [
                    ['CU', Valid::REQUIRED],
                    ['CU', Valid::MAX_LENGTH, 12],
                ],
                'messages' => [
                    Valid::MAX_LENGTH => 'カスタムメッセージ[:max]',
                ]
            ],
            'birthday' => [
                'label' => '生年月日',
                'rule'  => [
                    ['C', Valid::REQUIRED],
                ],
            ],
            'no_label' => [
            ],
            'bank' => [
                'label' => '振込先',
                'rule'  => [
                    ['CU', Valid::REQUIRED],
                ],
                'nest' => [
                    'bank_name' => [
                        'label' => '銀行名',
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                        ],
                    ],
                    'branch' => [
                        'label' => ':parent：支店',
                        'nest'  => [
                            'code' => [
                                'label' => ':parentコード',
                            ],
                            'name' => [
                                'label' => ':parent名',
                            ],
                        ],
                    ],
                ],
            ],
            'shipping_addresses' => [
                'label' => '送付先',
                'rule'  => [
                    ['CU', Valid::REQUIRED],
                ],
                'nests' => [
                    'zip' => [
                        'label' => ':parent郵便番号',
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                        ],
                    ],
                    'address' => [
                        'label' => '住所',
                        'rule'  => [
                            ['CU', Valid::REQUIRED],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test___cunstract()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );

        $this->assertInstanceOf(Context::class, $c);
    }

    public function test_initBy()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );

        $this->assertSame(null, $c->field);
        $this->assertSame(null, $c->value);
        $this->assertSame(null, $c->label);

        $c->initBy('name');

        $this->assertSame('name', $c->field);
        $this->assertSame('John Smith', $c->value);
        $this->assertSame('氏名', $c->label);

        $c->initBy('dummy');
        $this->assertSame('dummy', $c->field);
        $this->assertSame(null, $c->value);
        $this->assertSame('Dummy', $c->label);
    }

    public function test_hasError()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );
        $this->assertFalse($c->hasError());
        $this->assertFalse($c->hasError('*'));
        $this->assertFalse($c->hasError('other'));

        $c->initBy('name');
        $this->assertFalse($c->hasError());
        $this->assertFalse($c->hasError('*'));
        $this->assertFalse($c->hasError('other'));

        $this->errors['dummy'][] = 'Dummy error.';
        $this->assertFalse($c->hasError());
        $this->assertTrue($c->hasError('*'));
        $this->assertFalse($c->hasError('other'));

        $this->errors['other'][] = 'Dummy error.';
        $this->assertFalse($c->hasError());
        $this->assertTrue($c->hasError('*'));
        $this->assertTrue($c->hasError('other'));

        $this->errors['name'][] = 'Dummy error.';
        $this->assertTrue($c->hasError());
        $this->assertTrue($c->hasError('*'));
        $this->assertTrue($c->hasError('other'));
    }

    public function test_isQuietAndQuiet()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );
        $this->assertFalse($c->isQuiet());
        $this->assertInstanceOf(Context::class, $c->quiet(true));
        $this->assertTrue($c->isQuiet());
    }

    public function test_blank()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );
        $this->assertTrue($c->blank());

        $c->initBy('name');
        $this->assertFalse($c->blank());

        $c->initBy('dummy');
        $this->assertTrue($c->blank());
    }

    public function test_isBlank()
    {
        $this->assertSame(true, Context::isBlank(null));
        $this->assertSame(true, Context::isBlank(''));
        $this->assertSame(true, Context::isBlank([]));
        $this->assertSame(false, Context::isBlank(0));
    }

    public function test_count()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith', 'empty_array' => [], 'array' => [1, 2, 3]],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );
        $c->initBy('name');
        $this->assertSame(1, $c->count());
        $this->assertSame(0, $c->count('empty_array'));
        $this->assertSame(3, $c->count('array'));
    }

    public function test_appendError()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );

        $this->assertNull($this->errors['global'] ?? null);
        $c->appendError('Required');
        $this->assertSame(['を入力して下さい。'], $this->errors['global'] ?? null);

        $c->initBy('name');
        $this->assertNull($this->errors['name'] ?? null);
        $c->appendError('Required');
        $this->assertSame(['氏名を入力して下さい。'], $this->errors['name'] ?? null);
    }

    public function test_appendError_withParam()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );

        $c->initBy('name');
        $this->assertNull($this->errors['name'] ?? null);
        $c->appendError('MaxLength', ['max' => 12]);
        $this->assertSame(['氏名は12文字以下で入力して下さい。'], $this->errors['name'] ?? null);
    }

    public function test_appendError_withAt()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );

        $this->assertNull($this->errors['global'] ?? null);
        $c->appendError('@Global error');
        $this->assertSame(['Global error'], $this->errors['global'] ?? null);

        $c->initBy('name');
        $this->assertNull($this->errors['name'] ?? null);
        $c->appendError('@Name error 1');
        $this->assertSame(['Name error 1'], $this->errors['name'] ?? null);
        $c->appendError('@Name error 2');
        $this->assertSame(['Name error 1', 'Name error 2'], $this->errors['name'] ?? null);
    }

    public function test_appendError_customMessageInRule()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name_withMessage']]
        );

        $c->initBy('name');
        $this->assertNull($this->errors['name'] ?? null);
        $c->appendError('MaxLength', ['max' => 12]);
        $this->assertSame(['カスタムメッセージ[12]'], $this->errors['name'] ?? null);
    }

    public function test_value()
    {
        $c = new Context(
            'C',
            [
                'name'     => 'John Smith',
                'birthday' => '2010-01-23',
            ],
            $this->errors,
            [
                'name'     => $this->rule_set['name'],
                'birthday' => $this->rule_set['birthday'],
            ]
        );

        $this->assertSame('John Smith', $c->value('name'));
        $this->assertSame('2010-01-23', $c->value('birthday'));
        $this->assertSame(null, $c->value('invalid'));
    }

    public function test_label()
    {
        $c = new Context(
            'C',
            [
                'name'      => 'John Smith',
                'birthday'  => '2010-01-23',
                'no_label'  => null,
                'translate' => null,
                'nested'    => [
                    'translate'         => null,
                    'outer_nest_define' => null,
                    'inner_nest_define' => null,
                    'parent'            => [
                        'child' => null,
                    ]
                ],
                'bank'      => [
                    'bank_name' => 'Sample Bank',
                    'branch'    => [
                        'code' => '123',
                        'name' => 'Foo',
                    ],
                ],
                'shipping_addresses' => [
                    [
                        'zip'     => '1230001',
                        'address' => '1-2-3, Foo town, Bar city',
                    ],
                    [
                        'zip'     => '3210003',
                        'address' => '3-2-1, Baz town, Foo city',
                    ]
                ],
            ],
            $this->errors,
            [
                'name'               => $this->rule_set['name'],
                'birthday'           => $this->rule_set['birthday'],
                'bank'               => $this->rule_set['bank'],
                'shipping_addresses' => $this->rule_set['shipping_addresses'],
            ]
        );

        $this->assertSame('氏名', $c->label('name'));
        $this->assertSame('生年月日', $c->label('birthday'));
        $this->assertSame('No Label', $c->label('no_label'));
        $this->assertSame('翻訳', $c->label('translate'));
        $this->assertSame('ネストの外で定義', $c->label('outer_nest_define'));
        $this->assertSame('Inner Nest Define', $c->label('inner_nest_define'));
        $this->assertSame('ネスト', $c->label('nested'));
        $this->assertSame('ネストした翻訳', $c->label('nested.translate'));
        $this->assertSame('ネストの外で定義', $c->label('nested.outer_nest_define'));
        $this->assertSame('ネストの中で定義', $c->label('nested.inner_nest_define'));
        $this->assertSame('ネストされた親', $c->label('nested.parent'));
        $this->assertSame('ネストされた親の子', $c->label('nested.parent.child'));
        $this->assertSame('振込先', $c->label('bank'));
        $this->assertSame('銀行名', $c->label('bank.bank_name'));
        $this->assertSame('振込先：支店', $c->label('bank.branch'));
        $this->assertSame('振込先：支店コード', $c->label('bank.branch.code'));
        $this->assertSame('振込先：支店名', $c->label('bank.branch.name'));
        $this->assertSame('送付先', $c->label('shipping_addresses'));
        $this->assertSame('送付先郵便番号', $c->label('shipping_addresses.zip'));
        $this->assertSame('住所', $c->label('shipping_addresses.address'));

        $nest = $c->initBy('nested')->nest();
        $this->assertSame('ネストした翻訳', $nest->label('translate'));
        $this->assertSame('ネストの外で定義', $nest->label('outer_nest_define'));
        $this->assertSame('ネストの中で定義', $nest->label('inner_nest_define'));
        $this->assertSame('ネストされた親', $nest->label('parent'));
        $this->assertSame('ネストされた親の子', $nest->label('parent.child'));

        $parent = $nest->initBy('parent')->nest();
        $this->assertSame('ネストされた親の子', $parent->label('child'));
    }

    public function test_labels()
    {
        $c = new Context(
            'C',
            [
                'name'     => 'John Smith',
                'birthday' => '2010-01-23',
                'no_label' => null,
                'bank'     => [
                    'bank_name' => 'Sample Bank',
                    'branch'    => [
                        'code' => '123',
                        'name' => 'Foo',
                    ],
                ],
            ],
            $this->errors,
            [
                'name'     => $this->rule_set['name'],
                'birthday' => $this->rule_set['birthday'],
                'bank'     => $this->rule_set['bank'],
            ]
        );

        $this->assertSame(
            '氏名／生年月日／振込先：支店',
            $c->labels(['name', 'birthday', 'bank.branch'])
        );
        $this->assertSame(
            '氏名, 生年月日, 振込先：支店',
            $c->labels(['name', 'birthday', 'bank.branch'], ', ')
        );
    }

    public function test_resolve()
    {
        $c = new Context(
            'C',
            [
                'name'     => 'John Smith',
                'no_label' => null,
                'bank'     => [
                    'bank_name' => 'Sample Bank',
                    'branch'    => [
                        'code' => '123',
                        'name' => 'Foo',
                    ],
                ],
            ],
            $this->errors,
            [
                'name' => $this->rule_set['name'],
                'bank' => $this->rule_set['bank'],
            ]
        );

        $this->assertSame([1, 1], $c->resolve(1));
        $this->assertSame(['abc', 'abc'], $c->resolve('abc'));
        $this->assertSame([null, 'No Label'], $c->resolve(':no_label'));
        $this->assertSame(['John Smith', '氏名'], $c->resolve(':name'));
        $this->assertSame(['123', '振込先：支店コード'], $c->resolve(':bank.branch.code'));
        $this->assertSame([1, '男性'], $c->resolve(Gender::MALE()));
    }

    public function test_pluck()
    {
        $c = new Context(
            'C',
            [
                'name'               => 'John Smith',
                'shipping_addresses' => [
                    [
                        'zip'     => '1230001',
                        'address' => '1-2-3, Foo town, Bar city',
                    ],
                    [
                        'zip'     => '3210003',
                        'address' => '3-2-1, Baz town, Foo city',
                    ]
                ],
            ],
            $this->errors,
            [
                'name'               => $this->rule_set['name'],
                'shipping_addresses' => $this->rule_set['shipping_addresses'],
            ]
        );

        $this->assertSame([[], null], $c->pluckNested(null));

        $c->initBy('name');
        $this->assertSame(
            [
                ['John Smith'],
                '氏名'
            ],
            $c->pluckNested(null)
        );

        $c->initBy('shipping_addresses');
        $this->assertSame(
            [
                [
                    [
                        'zip'     => '1230001',
                        'address' => '1-2-3, Foo town, Bar city',
                    ],
                    [
                        'zip'     => '3210003',
                        'address' => '3-2-1, Baz town, Foo city',
                    ]
                ],
                '送付先'
            ],
            $c->pluckNested(null)
        );
        $this->assertSame(
            [
                ['1230001', '3210003'],
                '送付先郵便番号'
            ],
            $c->pluckNested('zip')
        );
        $this->assertSame(
            [
                ['1-2-3, Foo town, Bar city', '3-2-1, Baz town, Foo city'],
                '送付先の住所'
            ],
            $c->pluckNested('address')
        );
    }

    public function test_ordinalize()
    {
        App::setLocale('en');
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );
        $this->assertSame('1st', $c->ordinalize(1));
    }

    public function test_grammar()
    {
        App::setLocale('en');
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );
        $this->assertSame(', ', $c->grammar('delimiter'));
    }

    public function test_crud()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );
        $this->assertSame('C', $c->crud());
    }

    public function test_parent()
    {
        $c = new Context(
            'C',
            [
                'name'   => 'John Smith',
                'nested' => [
                    'name' => 'Bob Smith',
                ],
            ],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );
        $nest = $c->initBy('nested')->nest();
        $this->assertSame(null, $c->parent());
        $this->assertSame($c, $nest->parent());
    }

    public function test_hasParent()
    {
        $c = new Context(
            'C',
            [
                'name'   => 'John Smith',
                'nested' => [
                    'name' => 'Bob Smith',
                ],
            ],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );
        $nest = $c->initBy('nested')->nest();
        $this->assertSame(false, $c->hasParent());
        $this->assertSame(true, $nest->hasParent());
    }

    public function test_nest()
    {
        $c = new Context(
            'C',
            [
                'name'     => 'John Smith',
                'birthday' => '2010-01-23',
                'no_label' => null,
                'bank'     => [
                    'bank_name' => 'Sample Bank',
                    'branch'    => [
                        'code' => '123',
                        'name' => 'Foo',
                    ],
                ],
                'shipping_addresses' => [
                    [
                        'zip'     => '1230001',
                        'address' => '1-2-3, Foo town, Bar city',
                    ],
                    [
                        'zip'     => '3210003',
                        'address' => '3-2-1, Baz town, Foo city',
                    ]
                ],
            ],
            $this->errors,
            [
                'name'               => $this->rule_set['name'],
                'birthday'           => $this->rule_set['birthday'],
                'bank'               => $this->rule_set['bank'],
                'shipping_addresses' => $this->rule_set['shipping_addresses'],
            ]
        );
        $c->initBy('name');
        $this->assertSame('John Smith', $c->value);

        $c->initBy('bank');
        $this->assertSame(
            [
                'bank_name' => 'Sample Bank',
                'branch'    => [
                    'code' => '123',
                    'name' => 'Foo',
                ],
            ],
            $c->value
        );

        $n1 = $c->nest();
        $n1->initBy('bank_name');
        $this->assertTrue($n1->hasParent());
        $this->assertSame($c, $n1->parent());
        $this->assertSame('Sample Bank', $n1->value);
        $this->assertSame('銀行名', $n1->label);
        $this->assertSame('123', $n1->value('branch.code'));
        $this->assertSame('振込先：支店コード', $n1->label('branch.code'));

        $n1->initBy('branch');
        $this->assertSame(
            [
                'code' => '123',
                'name' => 'Foo',
            ],
            $n1->value
        );
        $this->assertSame('振込先：支店', $n1->label);
        $this->assertSame('Sample Bank', $n1->value('bank_name'));
        $this->assertSame('銀行名', $n1->label('bank_name'));

        $n2 = $n1->nest();
        $n2->initBy('code');
        $this->assertTrue($n2->hasParent());
        $this->assertSame($n1, $n2->parent());
        $this->assertSame($c, $n2->parent()->parent());
        $this->assertSame('123', $n2->value);
        $this->assertSame('振込先：支店コード', $n2->label);
        $this->assertSame('Foo', $n2->value('name'));
        $this->assertSame('振込先：支店名', $n2->label('name'));

        $c->initBy('shipping_addresses');
        $this->assertSame(
            [
                [
                    'zip'     => '1230001',
                    'address' => '1-2-3, Foo town, Bar city',
                ],
                [
                    'zip'     => '3210003',
                    'address' => '3-2-1, Baz town, Foo city',
                ]
            ],
            $c->value
        );

        $n1 = $c->nest(0);
        $n1->initBy('zip');
        $this->assertTrue($n1->hasParent());
        $this->assertSame($c, $n1->parent());
        $this->assertSame('1230001', $n1->value);
        $this->assertSame('送付先郵便番号', $n1->label);
        $this->assertSame('1-2-3, Foo town, Bar city', $n1->value('address'));
        $this->assertSame('住所', $n1->label('address'));

        $n1->initBy('address');
        $this->assertSame('1-2-3, Foo town, Bar city', $n1->value);
        $this->assertSame('住所', $n1->label);
        $this->assertSame('送付先郵便番号', $n1->label('zip'));

        $n1 = $c->nest(1);
        $n1->initBy('zip');
        $this->assertTrue($n1->hasParent());
        $this->assertSame($c, $n1->parent());
        $this->assertSame('3210003', $n1->value);
        $this->assertSame('送付先郵便番号', $n1->label);
        $this->assertSame('3-2-1, Baz town, Foo city', $n1->value('address'));
        $this->assertSame('住所', $n1->label('address'));

        $n1->initBy('address');
        $this->assertSame('3-2-1, Baz town, Foo city', $n1->value);
        $this->assertSame('住所', $n1->label);
        $this->assertSame('送付先郵便番号', $n1->label('zip'));
    }

    public function test_setExtra()
    {
        $c = new Context(
            'C',
            [
                'name'     => 'John Smith',
                'birthday' => '2010-01-23',
            ],
            $this->errors,
            ['name' => $this->rule_set['name']]
        );
        $c->initBy('name');
        $c->setExtra('key', 'value');
        $this->assertSame('value', $c->extra('key'));

        $c->initBy('birthday');
        $this->assertSame(null, $c->extra('key'));
    }
}
