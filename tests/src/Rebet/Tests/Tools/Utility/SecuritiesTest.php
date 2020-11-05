<?php
namespace Rebet\Tests\Tools\Utility;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Config\Exception\ConfigNotDefineException;
use Rebet\Tools\Utility\Securities;

class SecuritiesTest extends RebetTestCase
{
    public function test_randomCode()
    {
        $this->assertSame(8, mb_strlen(Securities::randomCode(8)));
        $this->assertSame('aaa', Securities::randomCode(3, 'a'));
    }

    public function test_hash()
    {
        putenv('DEFAULT_HASH_SALT=salt');
        putenv('DEFAULT_HASH_PEPPER=pepper');
        $this->assertSame(Securities::hash('password'), Securities::hash('password'));
        $this->assertNotSame(Securities::hash('password'), Securities::hash('p@ssword'));
    }

    public function test_hash_unsetSalt()
    {
        putenv('DEFAULT_HASH_SALT');
        putenv('DEFAULT_HASH_PEPPER=pepper');
        $this->expectException(ConfigNotDefineException::class);
        $this->expectExceptionMessage("Required config Rebet\Tools\Utility\Securities.hash.salt is blank or not define.");
        $this->assertSame(Securities::hash('password'), Securities::hash('password'));
    }

    public function test_hash_unsetPepper()
    {
        putenv('DEFAULT_HASH_SALT=salt');
        putenv('DEFAULT_HASH_PEPPER');
        $this->expectException(ConfigNotDefineException::class);
        $this->expectExceptionMessage("Required config Rebet\Tools\Utility\Securities.hash.pepper is blank or not define.");
        $this->assertSame(Securities::hash('password'), Securities::hash('password'));
    }

    public function test_hash_withArgs()
    {
        putenv('DEFAULT_HASH_SALT');
        putenv('DEFAULT_HASH_PEPPER');
        $this->assertSame(Securities::hash('password', 'salt', 'pepper'), Securities::hash('password', 'salt', 'pepper'));
        $this->assertNotSame(Securities::hash('password', 'salt', 'pepper'), Securities::hash('p@ssword', 'salt', 'pepper'));
    }

    public function test_randomHash()
    {
        $this->assertNotSame(Securities::randomHash(), Securities::randomHash());
    }

    public function test_encrypt()
    {
        for ($i=0; $i < 20; $i++) {
            $plain     = Securities::randomCode(mt_rand(12, 32));
            $secretKey = Securities::randomCode(mt_rand(3, 8));
            $encrypted = Securities::encrypt($plain, $secretKey);
            $decrypted = Securities::decrypt($encrypted, $secretKey);
            $this->assertSame($plain, $decrypted);
        }

        $plain     = 'This is pen';
        $secretKey = 'Test';
        $encrypted = Securities::encrypt($plain, $secretKey);
        $decrypted = Securities::decrypt($encrypted.'a', $secretKey);
        $this->assertNotSame($plain, $decrypted);
    }

    public function test_decrypt()
    {
        for ($i=0; $i < 20; $i++) {
            $plain     = Securities::randomCode(mt_rand(12, 32));
            $secretKey = Securities::randomCode(mt_rand(3, 8));
            $encrypted = Securities::encrypt($plain, $secretKey);
            $decrypted = Securities::decrypt($encrypted, $secretKey);
            $this->assertSame($plain, $decrypted);
        }

        $plain     = 'This is pen';
        $secretKey = 'Test';
        $encrypted = Securities::encrypt($plain, $secretKey);
        $decrypted = Securities::decrypt($encrypted.'a', $secretKey);
        $this->assertNotSame($plain, $decrypted);
    }
}
