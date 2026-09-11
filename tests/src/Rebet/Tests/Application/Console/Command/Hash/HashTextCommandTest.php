<?php
namespace Rebet\Tests\Application\Console\Command\Hash;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebet\Application\Console\Command\Hash\HashTextCommand;
use Rebet\Tests\RebetConsoleTestCase;
use Rebet\Tools\Utility\Securities;

class HashTextCommandTest extends RebetConsoleTestCase
{
    const AVIRABLE_COMMANDS = [HashTextCommand::class];

    public static function dataExecutes() : array
    {
        return [
            ['foobar'],
            ['foobar', ['--salt' => 'salt', '--pepper' => 'pepper']],
            ['foobar', ['--algorithm' => 'SHA512']],
            ['foobar', ['--stretching' => '3']],
            ['foobar', ['--salt' => 'salt', '--pepper' => 'pepper', '--algorithm' => 'SHA512', '--stretching' => '3']],
        ];
    }

    #[DataProvider('dataExecutes')]
    public function test_execute(string $text, array $options = [])
    {
        $tester = $this->getCommandTester(HashTextCommand::NAME);
        $status = $tester->execute(array_merge(['text' => $text], $options));
        $this->assertSame(0, $status);
        $display = $tester->getDisplay();
        $this->assertStringStartsWith("Hashed text: ", $display);

        $expect = Securities::hash(
            $text,
            $options['--salt'] ?? null,
            $options['--pepper'] ?? null,
            $options['--algorithm'] ?? null,
            isset($options['--stretching']) ? (int) $options['--stretching'] : null
        );
        $this->assertSame("Hashed text: {$expect}\n", $display);
    }
}
