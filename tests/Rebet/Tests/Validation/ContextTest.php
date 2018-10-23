<?php
namespace Rebet\Tests\Validation;

use Rebet\Tests\RebetTestCase;
use Rebet\Validation\Context;
use Rebet\Translation\Translator;
use Rebet\Translation\FileLoader;
use Rebet\Validation\Validator;
use Rebet\Validation\Valid;

class ContextTest extends RebetTestCase
{
    private $errors;
    private $translator;
    private $rule_set;
    
    public function setup()
    {
        parent::setUp();
        $this->errors     = [];
        $this->translator = new Translator(new FileLoader(Validator::config('resources_dir')));
        $this->rule_set   = [
            'name' => [
                'label' => '氏名',
                'rule' => [
                    ['CU', Valid::REQUIRED.'!'],
                ]
            ],
            'birthday' => [
                'label' => '生年月日',
                'rule' => [
                    ['C', Valid::REQUIRED.'!'],
                ],
            ],
            'no_label' => [
            ],
            'bank' => [
                'label' => '振込先',
                'rule'  => [
                    ['CU', Valid::REQUIRED.'!'],
                ],
                'nest' => [
                    'bank_name' => [
                        'label' => '銀行名',
                        'rule' => [
                            ['CU', Valid::REQUIRED.'!'],
                        ],
                    ],
                    'branch' => [
                        'label' => ':parent：支店',
                        'nest' => [
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
                    ['CU', Valid::REQUIRED.'!'],
                ],
                'nests' => [
                    'zip' => [
                        'label' => ':parent郵便番号',
                        'rule' => [
                            ['CU', Valid::REQUIRED.'!'],
                        ],
                    ],
                    'address' => [
                        'label' => ':parent住所',
                        'rule' => [
                            ['CU', Valid::REQUIRED.'!'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_cunstract()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']],
            $this->translator
        );

        $this->assertInstanceOf(Context::class, $c);
    }

    public function test_initBy()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']],
            $this->translator
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
            ['name' => $this->rule_set['name']],
            $this->translator
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

    public function test_empty()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']],
            $this->translator
        );
        $this->assertTrue($c->empty());

        $c->initBy('name');
        $this->assertFalse($c->empty());

        $c->initBy('dummy');
        $this->assertTrue($c->empty());
    }

    public function test_appendError()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']],
            $this->translator
        );

        $this->assertNull($this->errors['global'] ?? null);
        $c->appendError('validation.Required');
        $this->assertSame(['を入力して下さい。'], $this->errors['global'] ?? null);

        $c->initBy('name');
        $this->assertNull($this->errors['name'] ?? null);
        $c->appendError('validation.Required');
        $this->assertSame(['氏名を入力して下さい。'], $this->errors['name'] ?? null);
    }

    public function test_appendError_withParam()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']],
            $this->translator
        );

        $c->initBy('name');
        $this->assertNull($this->errors['name'] ?? null);
        $c->appendError('validation.LengthMax', ['max' => 12]);
        $this->assertSame(['氏名は12文字以下で入力して下さい。'], $this->errors['name'] ?? null);
    }

    public function test_appendError_withAt()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']],
            $this->translator
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
            ],
            $this->translator
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
            ],
            $this->translator
        );

        $this->assertSame('氏名', $c->label('name'));
        $this->assertSame('生年月日', $c->label('birthday'));
        $this->assertSame('No Label', $c->label('no_label'));
        $this->assertSame('振込先', $c->label('bank'));
        $this->assertSame('銀行名', $c->label('bank.bank_name'));
        $this->assertSame('振込先：支店', $c->label('bank.branch'));
        $this->assertSame('振込先：支店コード', $c->label('bank.branch.code'));
        $this->assertSame('振込先：支店名', $c->label('bank.branch.name'));
        $this->assertSame('送付先', $c->label('shipping_addresses'));
        $this->assertSame('送付先郵便番号', $c->label('shipping_addresses.zip'));
        $this->assertSame('送付先住所', $c->label('shipping_addresses.address'));
    }

    public function test_crud()
    {
        $c = new Context(
            'C',
            ['name' => 'John Smith'],
            $this->errors,
            ['name' => $this->rule_set['name']],
            $this->translator
        );
        $this->assertSame('C', $c->crud());
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
            ],
            $this->translator
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
        $this->assertSame('送付先住所', $n1->label('address'));

        $n1->initBy('address');
        $this->assertSame('1-2-3, Foo town, Bar city', $n1->value);
        $this->assertSame('送付先住所', $n1->label);
        $this->assertSame('送付先郵便番号', $n1->label('zip'));

        $n1 = $c->nest(1);
        $n1->initBy('zip');
        $this->assertTrue($n1->hasParent());
        $this->assertSame($c, $n1->parent());
        $this->assertSame('3210003', $n1->value);
        $this->assertSame('送付先郵便番号', $n1->label);
        $this->assertSame('3-2-1, Baz town, Foo city', $n1->value('address'));
        $this->assertSame('送付先住所', $n1->label('address'));

        $n1->initBy('address');
        $this->assertSame('3-2-1, Baz town, Foo city', $n1->value);
        $this->assertSame('送付先住所', $n1->label);
        $this->assertSame('送付先郵便番号', $n1->label('zip'));
    }
}
