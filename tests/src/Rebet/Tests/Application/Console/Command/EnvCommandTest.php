<?php
namespace Rebet\Tests\Application\Console\Command;

use Rebet\Application\Console\Command\EnvCommand;
use Rebet\Tests\RebetConsoleTestCase;

class EnvCommandTest extends RebetConsoleTestCase
{
    const AVIRABLE_COMMANDS = [EnvCommand::class];

    public function test_execute()
    {
        $tester = $this->getCommandTester(EnvCommand::NAME);
        $status = $tester->execute([]);
        $this->assertSame(0, $status);
        $this->assertSame("Current application environment: unittest.".PHP_EOL, $tester->getDisplay());
    }
}
