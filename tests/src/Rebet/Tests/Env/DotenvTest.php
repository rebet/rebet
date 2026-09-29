<?php

declare(strict_types=1);

namespace Rebet\Tests\Env;

use Dotenv\Exception\InvalidPathException;
use Override;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Rebet\Application\App;
use Rebet\Env\Dotenv;
use Rebet\Tests\RebetTestCase;

class DotenvTest extends RebetTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_init_notfound(): void
    {
        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage("Unable to read any of the environment file(s) at");

        Dotenv::load(__DIR__);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_init(): void
    {
        Dotenv::load(App::structure()->env(), '.env');
        $this->assertSame('unittest', \getenv('APP_ENV'));
    }
}
