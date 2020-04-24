<?php
namespace Rebet\Tests\Console\Command;

use Rebet\Foundation\Console\Command\InitCommand;
use Rebet\Tests\RebetTestCase;

class CommandTest extends RebetTestCase
{
    public function test_execute()
    {
        // @todo implements
        $tester = $this->getTester(InitCommand::NAME);
        $status = $tester->execute([]);
        $this->assertSame(0, $status);
        $this->assertSame("Application ready! Build something amazing.".PHP_EOL, $tester->getDisplay());
    }
}
