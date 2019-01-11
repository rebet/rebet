<?php
namespace Rebet\Tests\Auth;

use Rebet\Auth\AuthUser;
use Rebet\DateTime\DateTime;
use Rebet\Tests\Common\Mock\Bank;
use Rebet\Tests\Common\Mock\User;
use Rebet\Tests\RebetTestCase;

class AuthUserTest extends RebetTestCase
{
    private $array_user_source;
    private $object_user_source;

    public function setUp()
    {
        parent::setUp();
        DateTime::setTestNow('2010-01-01 12:34:56');

        $this->array_user_source = [
            'user_id'     => 2,
            'role'        => 'user',
            'name'        => 'User',
            'signin_id'   => 'user',
            'email'       => 'user@rebet.com',
            'password'    => '$2y$04$o9wMO8hXHHFpoNdLYRBtruWIUjPMU3Jqw9JAS0Oc7LOXiHFfn.7F2',  // password: user
            'api_token'   => 'token_2',
            'resigned_at' => null
        ];

        $user           = new User();
        $user->user_id  = 2;
        $user->role     = 'user';
        $user->name     = 'User';
        $user->birthday = DateTime::createDateTime('1991-02-03')->setDefaultFormat('Y-m-d');
        
        $this->object_user_source = $user;

        $bank          = new Bank();
        $bank->user_id = $user->user_id;
        $bank->name    = 'Sample Bank';
        $bank->branch  = 'Sample Branch';
        $bank->number  = '1234567';
        $bank->holder  = 'User';

        $user->bank = $bank;
    }

    public function test___construct()
    {
        $this->assertInstanceOf(AuthUser::class, new AuthUser($this->array_user_source));
        $this->assertInstanceOf(AuthUser::class, new AuthUser($this->object_user_source));
    }
}
