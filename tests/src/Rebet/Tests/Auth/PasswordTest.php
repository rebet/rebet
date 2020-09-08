<?php
namespace Rebet\Tests\Auth;

use Rebet\Auth\Password;
use Rebet\Tests\RebetTestCase;

class PasswordTest extends RebetTestCase
{
    /**
     * @dataProvider dataPasswords
     */
    public function test_hashAndVerify(?string $password, $algorithm = null, ?array $options = null)
    {
        $hash = Password::hash($password, $algorithm, $options);
        $this->assertTrue(Password::verify($password, $hash));
    }

    public function dataPasswords() : array
    {
        return [
            ['password', null, null],
            ['password', PASSWORD_DEFAULT, null],
            ['漢字かな' , PASSWORD_DEFAULT, ['cost' => 4]],
            ['p@ssw0rd', PASSWORD_BCRYPT, null],
            ['foobar'  , PASSWORD_BCRYPT, ['cost' => 4]],
            ['hogehoge', PASSWORD_BCRYPT, ['cost' => 12]],
        ];
    }

    public function test_hash()
    {
        $this->assertNull(Password::hash(null));
        $this->assertNotNull(Password::hash('password'));
    }

    public function test_verify()
    {
        $password = 'password';
        $hash     = Password::hash($password);
        $this->assertTrue(Password::verify($password, $hash));
        $this->assertFalse(Password::verify(null, $hash));
        $this->assertFalse(Password::verify($password, null));
        $this->assertFalse(Password::verify(null, null));
    }

    public function test_needsRehash()
    {
        $hash = Password::hash('password', PASSWORD_BCRYPT, ['cost' => 5]);
        $this->assertTrue(Password::needsRehash($hash, PASSWORD_BCRYPT, ['cost' => 4]));
        $this->assertFalse(Password::needsRehash($hash, PASSWORD_BCRYPT, ['cost' => 5]));
        $this->assertTrue(Password::needsRehash($hash, PASSWORD_BCRYPT, ['cost' => 6]));
        $this->assertFalse(Password::needsRehash(null, PASSWORD_BCRYPT, ['cost' => 6]));
    }
}
