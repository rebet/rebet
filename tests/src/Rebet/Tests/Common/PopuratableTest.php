<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\Mock\Address;
use Rebet\Tests\Mock\Customer;
use Rebet\Tests\Mock\Entity\Bank;
use Rebet\Tests\RebetTestCase;
use Rebet\Validation\ValidData;

class PopulatableTest extends RebetTestCase
{
    public $valid_data;

    protected function setUp() : void
    {
        parent::setUp();
        $this->valid_data = new ValidData([
            'name'               => 'John Smith',
            'altanate_name'      => 'JOHN SMITH',
            'birthday'           => '1987-01-23',
            'bank'               => [
                'name'       => 'SampleBank',
                'short_name' => 'SB',
                'branch'     => 'FooBranch',
                'number'     => '1234567',
                'holder'     => 'John Smith',
                'location'   => [
                    'zip'        => '1230003',
                    'prefecture' => '03',
                    'address'    => '2171 Scenic Way Springfield, IL 62701',
                ],
            ],
            'shipping_addresses' => [
                [
                    'zip'        => '1230001',
                    'prefecture' => '01',
                    'address'    => '1-2-3, Sample street, Test city',
                ],
                [
                    'zip'        => '9870002',
                    'prefecture' => '31',
                    'address'    => 'Baz bldg 12F, 1-2, Bar street, Foo city',
                ]
            ],
        ]);
    }

    public function test_populate()
    {
        $customer = new Customer();
        $customer->populate($this->valid_data);

        $this->assertSame('John Smith', $customer->name);
        $this->assertSame('1987-01-23', $customer->birthday);
        $this->assertNull($customer->bank ?? null);
        $this->assertNull($customer->bank->location ?? null);
        $this->assertNull($customer->shipping_addresses ?? null);
    }

    public function test_populateOptionEmbeds()
    {
        $customer = new Customer();
        $customer->populate($this->valid_data, [
            'embeds' => [
                Customer::class => [
                    'bank'               => Bank::class,
                    'shipping_addresses' => Address::class,
                ],
                Bank::class => [
                    'location' => Address::class,
                ],
            ],
        ]);

        $this->assertSame('John Smith', $customer->name);
        $this->assertInstanceOf(Bank::class, $customer->bank);
        $this->assertSame('SampleBank', $customer->bank->name);
        $this->assertInstanceOf(Address::class, $customer->bank->location);
        $this->assertSame('1230003', $customer->bank->location->zip);
        $this->assertInstanceOf(Address::class, $customer->shipping_addresses[0]);
        $this->assertSame('1230001', $customer->shipping_addresses[0]->zip);


        $valid_data = new ValidData([
            'name'               => 'John Smith',
            'altanate_name'      => 'JOHN SMITH',
            'birthday'           => '1987-01-23',
            'shipping_addresses' => [
                [
                    'zip'        => '1230001',
                    'prefecture' => '01',
                    'address'    => '1-2-3, Sample street, Test city',
                    'bank'       => [
                        'name'       => 'SampleBank',
                        'branch'     => 'FooBranch',
                        'number'     => '1234567',
                        'holder'     => 'John Smith',
                        'location'   => [
                            'zip'        => '1230003',
                            'prefecture' => '03',
                            'address'    => '2171 Scenic Way Springfield, IL 62701',
                        ],
                    ],
                ],
                [
                    'zip'        => '9870002',
                    'prefecture' => '31',
                    'address'    => 'Baz bldg 12F, 1-2, Bar street, Foo city',
                    'bank'       => [
                        'name'       => 'FooBarBank',
                        'branch'     => 'BarBranch',
                        'number'     => '7654321',
                        'holder'     => 'Jane Smith',
                    ],
                ]
            ],
        ]);
        $customer = new Customer();
        $customer->populate($valid_data, [
            'embeds' => [
                Customer::class => [
                    'shipping_addresses' => Address::class,
                ],
                Address::class => [
                    'bank' => Bank::class,
                ],
                Bank::class => [
                    'location' => Address::class,
                ],
            ],
        ]);
        $this->assertSame('John Smith', $customer->name);
        $this->assertInstanceOf(Address::class, $customer->shipping_addresses[0]);
        $this->assertSame('1230001', $customer->shipping_addresses[0]->zip);
        $this->assertInstanceOf(Bank::class, $customer->shipping_addresses[0]->bank);
        $this->assertSame('SampleBank', $customer->shipping_addresses[0]->bank->name);
        $this->assertInstanceOf(Address::class, $customer->shipping_addresses[0]->bank->location);
        $this->assertSame('1230003', $customer->shipping_addresses[0]->bank->location->zip);

        $this->assertSame('9870002', $customer->shipping_addresses[1]->zip);
        $this->assertInstanceOf(Bank::class, $customer->shipping_addresses[1]->bank);
        $this->assertSame('FooBarBank', $customer->shipping_addresses[1]->bank->name);
        $this->assertNull($customer->shipping_addresses[1]->bank->location ?? null);
    }

    public function test_populateOptionAlias()
    {
        $customer = new Customer();
        $customer->populate($this->valid_data, [
            'embeds' => [
                Customer::class => [
                    'bank'               => Bank::class,
                    'shipping_addresses' => Address::class,
                ],
            ],
            'aliases' => [
                'name' => 'altanate_name',
                'bank' => [
                    'name' => 'short_name'
                ],
            ],
        ]);

        $this->assertSame('JOHN SMITH', $customer->name);
        $this->assertSame('1987-01-23', $customer->birthday);
        $this->assertSame('SB', $customer->bank->name);
        $this->assertSame(null, $customer->bank->location ?? null);
        $this->assertSame('31', $customer->shipping_addresses[1]->prefecture);
    }

    public function test_populateOptionInclude()
    {
        $customer = new Customer();
        $customer->populate($this->valid_data, [
            'embeds' => [
                Customer::class => [
                    'bank'               => Bank::class,
                    'shipping_addresses' => Address::class,
                ],
            ],
            'includes' => [
                'name',
                'bank' => [
                    'name'
                ],
            ],
        ]);

        $this->assertSame('John Smith', $customer->name);
        $this->assertNull($customer->birthday);
        $this->assertInstanceOf(Bank::class, $customer->bank);
        $this->assertSame('SampleBank', $customer->bank->name);
        $this->assertSame(null, $customer->bank->location ?? null);
        $this->assertNull($customer->bank->branch);
        $this->assertSame(null, $customer->shipping_addresses);
    }

    public function test_populateOptionExclude()
    {
        $customer = new Customer();
        $customer->populate($this->valid_data, [
            'embeds' => [
                Customer::class => [
                    'bank'               => Bank::class,
                    'shipping_addresses' => Address::class,
                ],
            ],
            'excludes' => [
                'name',
                'bank' => [
                    'name'
                ],
            ],
        ]);

        $this->assertNull($customer->name);
        $this->assertSame('1987-01-23', $customer->birthday);
        $this->assertInstanceOf(Bank::class, $customer->bank);
        $this->assertNull($customer->bank->name);
        $this->assertSame('FooBranch', $customer->bank->branch);
        $this->assertInstanceOf(Address::class, $customer->shipping_addresses[0]);
        $this->assertSame('1230001', $customer->shipping_addresses[0]->zip);
    }
}
