<?php
namespace Rebet\Tests\Common;

use PHPUnit\Framework\TestCase;
use Rebet\Common\SecurityUtil;

class SecurityUtilTest extends TestCase {
    public function test_randomCode() {
        $this->assertSame(8, mb_strlen(SecurityUtil::randomCode(8)));
        $this->assertSame('aaa', SecurityUtil::randomCode(3,'a'));
    }

    public function test_hash() {
        $this->assertSame(SecurityUtil::hash('password'), SecurityUtil::hash('password'));
        $this->assertNotSame(SecurityUtil::hash('password'), SecurityUtil::hash('p@ssword'));
    }

    public function test_randomHash() {
        $this->assertNotSame(SecurityUtil::randomHash(), SecurityUtil::randomHash());
    }

    public function test_encript() {
        for ($i=0; $i < 100; $i++) { 
            $plain     = SecurityUtil::randomCode(mt_rand(12, 32));
            $secretKey = SecurityUtil::randomCode(mt_rand(3, 8));
            $encrypted = SecurityUtil::encript($plain, $secretKey);
            $decrypted = SecurityUtil::decript($encrypted, $secretKey);
            $this->assertSame($plain, $decrypted);
        }

        $plain     = 'This is pen';
        $secretKey = 'Test';
        $encrypted = SecurityUtil::encript($plain, $secretKey);
        $decrypted = SecurityUtil::decript($encrypted.'a', $secretKey);
        $this->assertNotSame($plain, $decrypted);
}

    public function test_decript() {
        for ($i=0; $i < 100; $i++) { 
            $plain     = SecurityUtil::randomCode(mt_rand(12, 32));
            $secretKey = SecurityUtil::randomCode(mt_rand(3, 8));
            $encrypted = SecurityUtil::encript($plain, $secretKey);
            $decrypted = SecurityUtil::decript($encrypted, $secretKey);
            $this->assertSame($plain, $decrypted);
        }

        $plain     = 'This is pen';
        $secretKey = 'Test';
        $encrypted = SecurityUtil::encript($plain, $secretKey);
        $decrypted = SecurityUtil::decript($encrypted.'a', $secretKey);
        $this->assertNotSame($plain, $decrypted);
    }
}
