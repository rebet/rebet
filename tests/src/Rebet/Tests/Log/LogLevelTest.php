<?php
namespace Rebet\Tests\Log;

use Rebet\Log\LogLevel;
use Rebet\Tests\RebetTestCase;

class LogLevelTest extends RebetTestCase
{
    public function test_errorTypeOf()
    {
        foreach ([
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_PARSE,
        ] as $error_type) {
            $this->assertSame(LogLevel::CRITICAL, LogLevel::errorTypeOf($error_type));
        }

        foreach ([
            E_ERROR,
            E_USER_ERROR,
            E_RECOVERABLE_ERROR,
        ] as $error_type) {
            $this->assertSame(LogLevel::ERROR, LogLevel::errorTypeOf($error_type));
        }

        foreach ([
            E_WARNING,
            E_USER_WARNING,
            E_CORE_WARNING,
            E_COMPILE_WARNING,
        ] as $error_type) {
            $this->assertSame(LogLevel::WARNING, LogLevel::errorTypeOf($error_type));
        }

        foreach ([
            E_NOTICE,
            E_USER_NOTICE,
            E_DEPRECATED,
            E_USER_DEPRECATED,
            E_STRICT,
        ] as $error_type) {
            $this->assertSame(LogLevel::NOTICE, LogLevel::errorTypeOf($error_type));
        }

        $this->assertSame(LogLevel::WARNING, LogLevel::errorTypeOf(99));
    }
}
