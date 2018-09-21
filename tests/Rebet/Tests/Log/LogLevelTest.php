<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Log\LogLevel;

class LogLevelTest extends RebetTestCase
{
    public function test_errorTypeOf()
    {
        foreach ([
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_PARSE,
        ] as $error_type) {
            $this->assertSame(LogLevel::FATAL(), LogLevel::errorTypeOf($error_type));
        }

        foreach ([
            E_ERROR,
            E_USER_ERROR,
            E_RECOVERABLE_ERROR,
        ] as $error_type) {
            $this->assertSame(LogLevel::ERROR(), LogLevel::errorTypeOf($error_type));
        }

        foreach ([
            E_WARNING,
            E_USER_WARNING,
            E_CORE_WARNING,
            E_COMPILE_WARNING,
        ] as $error_type) {
            $this->assertSame(LogLevel::WARN(), LogLevel::errorTypeOf($error_type));
        }

        foreach ([
            E_NOTICE,
            E_USER_NOTICE,
            E_DEPRECATED,
            E_USER_DEPRECATED,
            E_STRICT,
        ] as $error_type) {
            $this->assertSame(LogLevel::TRACE(), LogLevel::errorTypeOf($error_type));
        }

        $this->assertSame(LogLevel::WARN(), LogLevel::errorTypeOf(99));
    }

    public function test_higherEqual()
    {
        $this->assertFalse(LogLevel::INFO()->higherEqual(LogLevel::FATAL()));
        $this->assertFalse(LogLevel::INFO()->higherEqual(LogLevel::ERROR()));
        $this->assertFalse(LogLevel::INFO()->higherEqual(LogLevel::WARN()));
        $this->assertTrue(LogLevel::INFO()->higherEqual(LogLevel::INFO()));
        $this->assertTrue(LogLevel::INFO()->higherEqual(LogLevel::DEBUG()));
        $this->assertTrue(LogLevel::INFO()->higherEqual(LogLevel::TRACE()));
    }

    public function test_lowerThan()
    {
        $this->assertTrue(LogLevel::INFO()->lowerThan(LogLevel::FATAL()));
        $this->assertTrue(LogLevel::INFO()->lowerThan(LogLevel::ERROR()));
        $this->assertTrue(LogLevel::INFO()->lowerThan(LogLevel::WARN()));
        $this->assertFalse(LogLevel::INFO()->lowerThan(LogLevel::INFO()));
        $this->assertFalse(LogLevel::INFO()->lowerThan(LogLevel::DEBUG()));
        $this->assertFalse(LogLevel::INFO()->lowerThan(LogLevel::TRACE()));
    }
}
