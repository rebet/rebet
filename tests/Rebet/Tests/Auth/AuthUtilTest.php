<?php
namespace Rebet\Tests\Auth;

use PHPUnit\Framework\TestCase;
use Rebet\Auth\AuthUtil;

class AuthUtilTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @expectedException Rebet\Auth\AuthenticateException
     * @expectedExceptionMessage Authenticate Failed.
     * @todo header check
     */
    public function test_basicAuthenticate() {
        ob_start();
        try {
            AuthUtil::basicAuthenticate(['id' => 'password']);
            $this->fail('No Exception');
        } finally {
            ob_end_clean();
            // $headers_list = xdebug_get_headers();
            // $this->assertContains('HTTP/1.0 401 Unauthorized', $headers_list);
            // $this->assertContains('WWW-Authenticate: Basic realm="Enter your ID and PASSWORD."', $headers_list);
            // $this->assertContains('Content-type: text/html; charset=utf-8', $headers_list);
            $this->markTestIncomplete('We should test about header() output.');        
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function test_basicAuthenticate_pass() {
        ob_start();
        $_SERVER['PHP_AUTH_USER'] = 'id';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $id = AuthUtil::basicAuthenticate(['id' => 'password']);
        $this->assertSame('id', $id);
        ob_end_clean();
    }

    /**
     * @runInSeparateProcess
     * @expectedException Rebet\Auth\AuthenticateException
     * @expectedExceptionMessage Authenticate Failed.
     * @todo header check
     */
    public function test_basicAuthenticate_faled() {
        ob_start();
        try {
            $_SERVER['PHP_AUTH_USER'] = 'id';
            $_SERVER['PHP_AUTH_PW']   = 'invalid';
            $id = AuthUtil::basicAuthenticate(['id' => 'password']);
            $this->fail('No Exception');
        } finally {
            ob_end_clean();
            // $headers_list = xdebug_get_headers();
            // $this->assertContains('HTTP/1.0 401 Unauthorized', $headers_list);
            // $this->assertContains('WWW-Authenticate: Basic realm="Enter your ID and PASSWORD."', $headers_list);
            // $this->assertContains('Content-type: text/html; charset=utf-8', $headers_list);
            $this->markTestIncomplete('We should test about header() output.');        
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function test_basicAuthenticate_hash() {
        ob_start();
        $_SERVER['PHP_AUTH_USER'] = 'id';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $id = AuthUtil::basicAuthenticate(
            ['id' => '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8'],
            function($password) { return sha1($password); }
        );
        $this->assertSame('id', $id);
        ob_end_clean();
    }
}
