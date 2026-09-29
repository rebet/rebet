<?php

declare(strict_types=1);

namespace Rebet\Tests;

use Override;
use Rebet\Console\Testable\ConsoleTestHelper;

/**
 * Rebet Console Test Case Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetConsoleTestCase extends RebetTestCase
{
    use ConsoleTestHelper;

    public const AVIRABLE_COMMANDS = [];

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpConsole(...static::AVIRABLE_COMMANDS);
    }
}
