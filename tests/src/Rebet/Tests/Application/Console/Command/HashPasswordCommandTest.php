<?php
namespace Rebet\Tests\Application\Console\Command;

use Rebet\Application\Console\Command\HashPasswordCommand;
use Rebet\Auth\Password;
use Rebet\Tests\RebetConsoleTestCase;
use Rebet\Tools\Utility\Strings;

class HashPasswordCommandTest extends RebetConsoleTestCase
{
    const COMMANDS = [HashPasswordCommand::class];

    // public function test_run()
    // {
    //     $this->execute('hash:password foobar -o \'{"cost": 8}\'');
    // }

    public function dataExecutes() : array
    {
        return [
            ['foobar'],
            ['foobar', ['--option' => '{"cost": 8}']],
            ['foobar', ['--algorithm' => PASSWORD_ARGON2I]],
            ['foobar', ['--algorithm' => PASSWORD_ARGON2I, '--option' => '{"time_cost": 3}']],
       ];
    }
    
    /**
     * @dataProvider dataExecutes
     */
    public function test_execute(string $password, array $options = [])
    {
        $tester = $this->getTester(HashPasswordCommand::NAME);
        $status = $tester->execute(array_merge(['password' => $password], $options));
        $this->assertSame(0, $status);
        $display = $tester->getDisplay();
        $this->assertStringStartsWith("Hashed password: ", $display);
        $this->assertTrue(Password::verify($password, Strings::ltrim(trim($display), "Hashed password: ")));
    }

    public function test_execute_jsonError()
    {
        $tester = $this->getTester(HashPasswordCommand::NAME);
        $status = $tester->execute(['password' => 'foobar', '--option' => '{cost: 8}']);
        $this->assertSame(1, $status);
        $display = $tester->getDisplay();
        $this->assertSame("Can not parse --option JSON : Syntax error\n", $display);
    }
}
