<?php
namespace Rebet\Tests\Validation;

use Rebet\Tests\RebetTestCase;
use Rebet\Validation\Validatable;
use Rebet\Tests\Validation\Mock\User;
use Rebet\Tests\Validation\Mock\Bank;
use Rebet\Http\Request;
use Rebet\Tests\Validation\Mock\Address;

class ValidatableTest extends RebetTestCase
{
    public $request;

    public function setUp()
    {
        parent::setUp();
        $this->request = Request::create('/test', 'POST', [
            'name'               => 'John Smith',
            'birthday'           => '1987-01-23',
            'bank'               => [
                'name'   => 'SampleBank',
                'branch' => 'FooBranch',
                'number' => '1234567',
                'holder' => 'John Smith',
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

    public function test_popurate() {
        $user = new User();
        $user->popurate($this->request->request->all());

        $this->assertSame('John Smith', $user->name);
        $this->assertInstanceOf(Bank::class, $user->bank);
        $this->assertSame('SampleBank', $user->bank->name);
        $this->assertInstanceOf(Address::class, $user->shipping_addresses[0]);
        $this->assertSame('1230001', $user->shipping_addresses[0]->zip);
    }

}