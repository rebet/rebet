<?php
namespace Rebet\Tests\Foundation\Console\Command;

use Rebet\Foundation\Console\Command\InitCommand;
use Rebet\Tests\RebetConsoleTestCase;

class InitCommandTest extends RebetConsoleTestCase
{
    const COMMANDS = [InitCommand::class];

    public function test_execute()
    {
        // @todo implements
        $tester = $this->getTester(InitCommand::NAME);
        $status = $tester->execute([]);
        $this->assertSame(0, $status);
        $this->assertSame("Application ready! Build something amazing.".PHP_EOL, $tester->getDisplay());
    }
}
