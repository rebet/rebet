<?php
namespace Rebet\Tests\Application\Console\Command\Hash;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebet\Application\Console\Command\Hash\HashHmacCommand;
use Rebet\Tests\RebetConsoleTestCase;
use Rebet\Tools\Utility\Securities;

class HashHmacCommandTest extends RebetConsoleTestCase
{
    const AVIRABLE_COMMANDS = [HashHmacCommand::class];

    public static function dataExecutes() : array
    {
        return [
            ['foobar'],
            ['foobar', ['--secret-key' => 'secret']],
            ['foobar', ['--algorithm'  => 'sha512']],
            ['foobar', ['--secret-key' => 'secret', '--algorithm' => 'sha512']],
        ];
    }

    #[DataProvider('dataExecutes')]
    public function test_execute(string $text, array $options = [])
    {
        $tester = $this->getCommandTester(HashHmacCommand::NAME);
        $status = $tester->execute(array_merge(['text' => $text], $options));
        $this->assertSame(0, $status);
        $display = $tester->getDisplay();
        $this->assertStringStartsWith("HMAC: ", $display);

        $expect = Securities::hmac($text, $options['--secret-key'] ?? null, $options['--algorithm'] ?? null);
        $this->assertSame("HMAC: {$expect}\n", $display);
    }
}
