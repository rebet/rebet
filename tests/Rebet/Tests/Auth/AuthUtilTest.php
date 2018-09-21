<?php
namespace Rebet\Tests\Auth;

use Rebet\Tests\RebetTestCase;
use Rebet\Auth\AuthUtil;

use Rebet\Common\System;

class AuthUtilTest extends RebetTestCase
{
    public function setUp()
    {
        System::mock_init();
    }

    /**
     * @expectedException Rebet\Auth\AuthenticateException
     * @expectedExceptionMessage Authenticate Failed.
     */
    public function test_basicAuthenticate()
    {
        try {
            AuthUtil::basicAuthenticate(['id' => 'password']);
            $this->fail('Never executed.');
        } finally {
            $headers = System::headers_list();
            $this->assertContains('HTTP/1.0 401 Unauthorized', $headers);
            $this->assertContains('WWW-Authenticate: Basic realm="Enter your ID and PASSWORD."', $headers);
            $this->assertContains('Content-type: text/html; charset=UTF-8', $headers);
        }
    }

    public function test_basicAuthenticate_pass()
    {
        $_SERVER['PHP_AUTH_USER'] = 'id';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $id = AuthUtil::basicAuthenticate(['id' => 'password']);
        $this->assertSame('id', $id);
    }

    /**
     * @expectedException Rebet\Auth\AuthenticateException
     * @expectedExceptionMessage Authenticate Failed.
     */
    public function test_basicAuthenticate_faled()
    {
        try {
            $_SERVER['PHP_AUTH_USER'] = 'id';
            $_SERVER['PHP_AUTH_PW']   = 'invalid';
            $id = AuthUtil::basicAuthenticate(['id' => 'password']);
            $this->fail('No Exception');
        } finally {
            $headers = System::headers_list();
            $this->assertContains('HTTP/1.0 401 Unauthorized', $headers);
            $this->assertContains('WWW-Authenticate: Basic realm="Enter your ID and PASSWORD."', $headers);
            $this->assertContains('Content-type: text/html; charset=UTF-8', $headers);
        }
    }

    public function test_basicAuthenticate_hash()
    {
        $_SERVER['PHP_AUTH_USER'] = 'id';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $id = AuthUtil::basicAuthenticate(
            ['id' => '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8'],
            function ($password) {
                return sha1($password);
            }
        );
        $this->assertSame('id', $id);
    }
}
