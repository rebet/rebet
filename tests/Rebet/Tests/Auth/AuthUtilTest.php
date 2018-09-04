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
        }
    }
}
