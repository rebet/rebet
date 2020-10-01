<?php
namespace Rebet\Tests\Tools;

use Rebet\Tools\Securities;
use Rebet\Tests\RebetTestCase;

class SecuritiesTest extends RebetTestCase
{
    public function test_randomCode()
    {
        $this->assertSame(8, mb_strlen(Securities::randomCode(8)));
        $this->assertSame('aaa', Securities::randomCode(3, 'a'));
    }

    public function test_hash()
    {
        $this->assertSame(Securities::hash('password'), Securities::hash('password'));
        $this->assertNotSame(Securities::hash('password'), Securities::hash('p@ssword'));
    }

    public function test_randomHash()
    {
        $this->assertNotSame(Securities::randomHash(), Securities::randomHash());
    }

    public function test_encrypt()
    {
        for ($i=0; $i < 100; $i++) {
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
        for ($i=0; $i < 100; $i++) {
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
