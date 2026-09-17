<?php
namespace Rebet\Tests\Application\Console\Command\Project;

use Rebet\Application\Console\Command\Project\ProjectInitCommand;
use Rebet\Tests\RebetConsoleTestCase;

class ProjectInitCommandTest extends RebetConsoleTestCase
{
    const AVIRABLE_COMMANDS = [ProjectInitCommand::class];

    public function test_execute()
    {
        // $this->execute('init');
        $this->assertTrue(true);

        // @todo implements
        // $tester = $this->getCommandTester(ProjectInitCommand::NAME);
        // $status = $tester->execute([]);
        // $this->assertSame(0, $status);
        // $this->assertSame("Application ready! Build something amazing.".PHP_EOL, $tester->getDisplay());
    }
}
