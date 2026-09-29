<?php

declare(strict_types=1);

namespace Rebet\Tests\Auth;

use Override;
use Rebet\Auth\BasicAuth;
use Rebet\Auth\Exception\AuthenticateException;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Testable\System;

class BasicAuthTest extends RebetTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_authenticate(): void
    {
        $this->expectException(AuthenticateException::class);
        $this->expectExceptionMessage("Authenticate Failed.");

        try {
            BasicAuth::authenticate(['id' => 'password']);
        } finally {
            $headers = System::headers_list();
            $this->assertContains('HTTP/1.0 401 Unauthorized', $headers);
            $this->assertContains('WWW-Authenticate: Basic realm="Enter your ID and PASSWORD."', $headers);
            $this->assertContains('Content-type: text/html; charset=UTF-8', $headers);
        }
    }

    public function test_authenticate_pass(): void
    {
        $_SERVER['PHP_AUTH_USER'] = 'id';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $id                       = BasicAuth::authenticate(['id' => 'password']);
        $this->assertSame('id', $id);
    }

    public function test_authenticate_faled(): void
    {
        $this->expectException(AuthenticateException::class);
        $this->expectExceptionMessage("Authenticate Failed.");

        try {
            $_SERVER['PHP_AUTH_USER'] = 'id';
            $_SERVER['PHP_AUTH_PW']   = 'invalid';
            $id                       = BasicAuth::authenticate(['id' => 'password']);
        } finally {
            $headers = System::headers_list();
            $this->assertContains('HTTP/1.0 401 Unauthorized', $headers);
            $this->assertContains('WWW-Authenticate: Basic realm="Enter your ID and PASSWORD."', $headers);
            $this->assertContains('Content-type: text/html; charset=UTF-8', $headers);
        }
    }

    public function test_authenticate_hash(): void
    {
        $_SERVER['PHP_AUTH_USER'] = 'id';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $id                       = BasicAuth::authenticate(
            ['id' => '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8'],
            fn($password) => sha1($password),
        );
        $this->assertSame('id', $id);
    }
}
