<?php
namespace Rebet\Tests;

use Rebet\Console\Testable\ConsoleTestHelper;

/**
 * Rebet Console Test Case Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetConsoleTestCase extends RebetTestCase
{
    use ConsoleTestHelper;

    const AVIRABLE_COMMANDS = [];

    public function setUp() : void {
        parent::setUp();
        $this->setUpConsole(...static::AVIRABLE_COMMANDS);
    }
}
