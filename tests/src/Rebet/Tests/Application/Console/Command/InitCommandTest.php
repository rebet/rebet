<?php
namespace Rebet\Tests\Application\Console\Command;

use Rebet\Application\Console\Command\InitCommand;
use Rebet\Tests\RebetConsoleTestCase;

class InitCommandTest extends RebetConsoleTestCase
{
    const COMMANDS = [InitCommand::class];

    public function test_execute()
    {
        // $this->execute('init');
        $this->assertTrue(true);

        // @todo implements
        // $tester = $this->getTester(InitCommand::NAME);
        // $status = $tester->execute([]);
        // $this->assertSame(0, $status);
        // $this->assertSame("Application ready! Build something amazing.".PHP_EOL, $tester->getDisplay());
    }
}
