<?php
namespace Rebet\Tests\Application\Console\Command\Crypto;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebet\Application\Console\Command\Crypto\CryptoDecryptCommand;
use Rebet\Tests\RebetConsoleTestCase;
use Rebet\Tools\Utility\Nets;
use Rebet\Tools\Utility\Securities;

class CryptoDecryptCommandTest extends RebetConsoleTestCase
{
    const AVIRABLE_COMMANDS = [CryptoDecryptCommand::class];

    public static function dataExecutes() : array
    {
        return [
            ['This is pen'],
            ['This is pen', ['--secret-key' => 'crypto_secret']],
            ['This is pen', ['--secret-key' => 'crypto_secret', '--hmac-secret-key' => 'hmac_secret']],
            ['This is pen', ['--secret-key' => 'crypto_secret', '--hmac-secret-key' => 'hmac_secret', '--hmac-algorithm' => 'sha512']],
        ];
    }

    #[DataProvider('dataExecutes')]
    public function test_execute(string $plain, array $options = [])
    {
        $encrypted = Nets::encodeBase64Url(Securities::encrypt(
            $plain,
            $options['--secret-key'] ?? null,
            null,
            $options['--hmac-secret-key'] ?? null,
            $options['--hmac-algorithm'] ?? null
        ));

        $tester = $this->getCommandTester(CryptoDecryptCommand::NAME);
        $status = $tester->execute(array_merge(['encrypted' => $encrypted], $options));
        $this->assertSame(0, $status);
        $this->assertSame("Decrypted: {$plain}\n", $tester->getDisplay());
    }

    public function test_execute_invalid()
    {
        $tester = $this->getCommandTester(CryptoDecryptCommand::NAME);
        $status = $tester->execute(['encrypted' => 'not-a-valid-encrypted-value']);
        $this->assertSame(1, $status);
        $this->assertSame("Decryption failed. The given text is invalid or was encrypted with different keys.\n", $tester->getDisplay());
    }

    public function test_execute_wrongSecretKey()
    {
        $encrypted = Nets::encodeBase64Url(Securities::encrypt('This is pen', 'crypto_secret'));

        $tester = $this->getCommandTester(CryptoDecryptCommand::NAME);
        $status = $tester->execute(['encrypted' => $encrypted, '--secret-key' => 'wrong_secret']);
        $this->assertSame(0, $status);
        $this->assertNotSame("Decrypted: This is pen\n", $tester->getDisplay());
    }
}
