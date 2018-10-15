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
            'altanate_name'      => 'JOHN SMITH',
            'birthday'           => '1987-01-23',
            'bank'               => [
                'name'       => 'SampleBank',
                'short_name' => 'SB',
                'branch'     => 'FooBranch',
                'number'     => '1234567',
                'holder'     => 'John Smith',
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

    public function test_popurate()
    {
        $user = new User();
        $user->popurate($this->request->request->all());

        $this->assertSame('John Smith', $user->name);
        $this->assertInstanceOf(Bank::class, $user->bank);
        $this->assertSame('SampleBank', $user->bank->name);
        $this->assertInstanceOf(Address::class, $user->shipping_addresses[0]);
        $this->assertSame('1230001', $user->shipping_addresses[0]->zip);
    }
    
    public function test_popurateOptionAlias()
    {
        $user = new User();
        $user->popurate($this->request->request->all(), null, [
            'aliases' => [
                'name' => 'altanate_name',
                'bank' => [
                    'name' => 'short_name'
                ],
            ],
        ]);
        
        $this->assertSame('JOHN SMITH', $user->name);
        $this->assertSame('1987-01-23', $user->birthday);
        $this->assertSame('SB', $user->bank->name);
        $this->assertSame('31', $user->shipping_addresses[1]->prefecture);
    }

    public function test_popurateOptionInclude()
    {
        $user = new User();
        $user->popurate($this->request->request->all(), null, [
            'includes' => [
                'name',
                'bank' => [
                    'name'
                ],
            ],
        ]);

        $this->assertSame('John Smith', $user->name);
        $this->assertNull($user->birthday);
        $this->assertInstanceOf(Bank::class, $user->bank);
        $this->assertSame('SampleBank', $user->bank->name);
        $this->assertNull($user->bank->branch);
        $this->assertSame([], $user->shipping_addresses);
    }
}
