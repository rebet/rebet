<?php
namespace Rebet\Tests\Console\Command;

use Rebet\Console\Command\Command;
use Rebet\Tests\RebetTestCase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTest extends RebetTestCase
{
    /** @var Command */
    protected $hello;

    protected function setUp() : void
    {
        parent::setUp();
        $this->hello          = new class() extends Command {
            const NAME        = 'Hello';
            const DESCRIPTION = 'Say Hello.';
            const ARGUMENTS   = [
                ['to', InputArgument::OPTIONAL, 'Say hello to someone.']
            ];
            const OPTIONS     = [
                ['meeting-time', 'mt', InputArgument::OPTIONAL, 'Meeting time of morning, noon or evening.'],
                ['command', 'c', InputArgument::OPTIONAL, 'Command to display', 'comment'],
            ];

            protected function handle()
            {
                switch ($this->option('meeting-time') ?? 'unknown') {
                    case 'morning': $hello = "Good morning"; break;
                    case 'noon':    $hello = "Good after noon"; break;
                    case 'evening': $hello = "Good evening"; break;
                    default: $hello        = 'Hello'; break;
                }
                if ($to = $this->argument('to')) {
                    $hello = "{$hello} {$to}";
                }
                $command = $this->option('command');
                $this->$command("{$hello}.");
            }
        };
    }

    public function test_configure()
    {
        $tester = new CommandTester($this->hello);

        $status = $tester->execute([]);
        $this->assertSame(0, $status);
        $this->assertSame("Hello.".PHP_EOL, $tester->getDisplay());
        $this->assertSame(['to' => null], $this->hello->arguments());
        $this->assertSame(true, $this->hello->hasArgument('to'));
        $this->assertSame(false, $this->hello->hasArgument('invalid'));
        $this->assertSame(null, $this->hello->argument('to'));
        $this->assertSame(['meeting-time' => null, 'command' => 'comment'], $this->hello->options());
        $this->assertSame(true, $this->hello->hasOption('meeting-time'));
        $this->assertSame(false, $this->hello->hasOption('mt'));
        $this->assertSame(false, $this->hello->hasOption('invalid'));
        $this->assertSame(null, $this->hello->option('meeting-time'));

        $status = $tester->execute(['to' => 'John']);
        $this->assertSame(0, $status);
        $this->assertSame("Hello John.".PHP_EOL, $tester->getDisplay());
        $this->assertSame(['to' => 'John'], $this->hello->arguments());
        $this->assertSame(true, $this->hello->hasArgument('to'));
        $this->assertSame('John', $this->hello->argument('to'));
        $this->assertSame(['meeting-time' => null, 'command' => 'comment'], $this->hello->options());

        $status = $tester->execute(['to' => 'John', '--meeting-time' => 'morning']);
        $this->assertSame(0, $status);
        $this->assertSame("Good morning John.".PHP_EOL, $tester->getDisplay());
        $this->assertSame(['to' => 'John'], $this->hello->arguments());
        $this->assertSame(['meeting-time' => 'morning', 'command' => 'comment'], $this->hello->options());
        $this->assertSame('morning', $this->hello->option('meeting-time'));

        $status = $tester->execute(['to' => 'John', '-mt' => 'noon']);
        $this->assertSame(0, $status);
        $this->assertSame("Good after noon John.".PHP_EOL, $tester->getDisplay());
        $this->assertSame(['to' => 'John'], $this->hello->arguments());
        $this->assertSame(['meeting-time' => 'noon', 'command' => 'comment'], $this->hello->options());

        $status = $tester->execute(['-c' => 'write']);
        $this->assertSame(0, $status);
        $this->assertSame("Hello.", $tester->getDisplay());

        $status = $tester->execute(['-c' => 'writeln']);
        $this->assertSame(0, $status);
        $this->assertSame("Hello.".PHP_EOL, $tester->getDisplay());

        $status = $tester->execute(['-c' => 'info']);
        $this->assertSame(0, $status);
        $this->assertSame("Hello.".PHP_EOL, $tester->getDisplay());

        $status = $tester->execute(['-c' => 'comment']);
        $this->assertSame(0, $status);
        $this->assertSame("Hello.".PHP_EOL, $tester->getDisplay());

        $status = $tester->execute(['-c' => 'question']);
        $this->assertSame(0, $status);
        $this->assertSame("Hello.".PHP_EOL, $tester->getDisplay());

        $status = $tester->execute(['-c' => 'error']);
        $this->assertSame(0, $status);
        $this->assertSame("Hello.".PHP_EOL, $tester->getDisplay());

        $status = $tester->execute(['-c' => 'warning']);
        $this->assertSame(0, $status);
        $this->assertSame("Hello.".PHP_EOL, $tester->getDisplay());
    }
}
