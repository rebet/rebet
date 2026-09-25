<?php
namespace Rebet\Tests\Console\Command;

use Rebet\Console\Command\Command;
use Rebet\Tests\RebetTestCase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTest extends RebetTestCase
{
    /** @var Command */
    protected $hello;

    protected function setUp() : void
    {
        parent::setUp();
        $this->hello = new class() extends Command {
            const NAME        = 'Hello';
            const DESCRIPTION = 'Say Hello.';
            const ARGUMENTS   = [
                ['to', InputArgument::OPTIONAL, 'Say hello to someone.']
            ];
            const OPTIONS = [
                ['meeting-time', 'mt', InputArgument::OPTIONAL, 'Meeting time of morning, noon or evening.'],
                ['command', 'c', InputArgument::OPTIONAL, 'Command to display', 'comment'],
            ];

            protected function handle()
            {
                switch ($this->option('meeting-time') ?? 'unknown') {
                    case 'morning': $hello = "Good morning";
                        break;
                    case 'noon':    $hello = "Good after noon";
                        break;
                    case 'evening': $hello = "Good evening";
                        break;
                    default: $hello = 'Hello';
                        break;
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

    public function test_choice_viaOption()
    {
        $choice = new class() extends Command {
            const NAME        = 'Choice';
            const DESCRIPTION = 'Choice test command.';
            const OPTIONS     = [
                ['fruit', 'f', InputOption::VALUE_OPTIONAL, 'Favorite fruit.'],
            ];

            protected function handle()
            {
                $this->writeln($this->choice("Favorite fruit : ", ['apple' => 'Apple', 'banana' => 'Banana'], 'fruit', 'apple'));
            }
        };
        $tester = new CommandTester($choice);

        // The option value matches a choice key (this used to be rejected, only labels/values matched).
        $status = $tester->execute(['--fruit' => 'banana']);
        $this->assertSame(0, $status);
        $this->assertSame("Favorite fruit : banana (via option)\nbanana".PHP_EOL, $tester->getDisplay());

        // The option value matches a choice value (label), which already worked before the fix.
        // (viaOption() returns the matched answer verbatim, so a label match yields the label as-is.)
        $status = $tester->execute(['--fruit' => 'Apple']);
        $this->assertSame(0, $status);
        $this->assertSame("Favorite fruit : Apple (via option)\nApple".PHP_EOL, $tester->getDisplay());
    }

    public function test_option_aliases()
    {
        $aliased = new class() extends Command {
            const NAME        = 'Aliased';
            const DESCRIPTION = 'Option alias test command.';
            const OPTIONS     = [
                [['long-name', 'ln'], 'x', InputOption::VALUE_OPTIONAL, 'A value option with a long alias.'],
                [['dry-run', 'dr'], null, InputOption::VALUE_NONE, 'A flag option with a long alias.'],
            ];

            protected function handle()
            {
                $this->writeln(var_export($this->option('long-name'), true));
                $this->writeln(var_export($this->option('ln'), true));
                $this->writeln(var_export($this->option('dry-run'), true));
                $this->writeln(var_export($this->option('dr'), true));
            }
        };

        // Registering both alias names does not collide over the shared shortcut.
        $this->assertSame(['long-name', 'ln', 'dry-run', 'dr'], array_keys($aliased->getDefinition()->getOptions()));
        $this->assertSame('long-name', $aliased->getDefinition()->getOptionForShortcut('x')->getName());

        // Neither given: both alias names resolve to the same (default) value.
        $tester = new CommandTester($aliased);
        $tester->execute([]);
        $this->assertSame("NULL\nNULL\nfalse\nfalse\n", $tester->getDisplay());

        // Given via the alias name: visible through both the primary and the alias name.
        $tester = new CommandTester($aliased);
        $tester->execute(['--ln' => 'hello', '--dr' => true]);
        $this->assertSame("'hello'\n'hello'\ntrue\ntrue\n", $tester->getDisplay());

        // Given via the primary name: visible through both the primary and the alias name.
        $tester = new CommandTester($aliased);
        $tester->execute(['--long-name' => 'world', '--dry-run' => true]);
        $this->assertSame("'world'\n'world'\ntrue\ntrue\n", $tester->getDisplay());

        // Given via the shortcut (which only the primary name can carry): resolves through both names.
        $tester = new CommandTester($aliased);
        $tester->execute(['-x' => 'viaShortcut']);
        $this->assertSame("'viaShortcut'\n'viaShortcut'\nfalse\nfalse\n", $tester->getDisplay());
    }
}
