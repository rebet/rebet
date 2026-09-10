<?php
namespace Rebet\Tests\Application\Console\Command\Crypto;

use PHPUnit\Framework\Attributes\DataProvider;
use Rebet\Application\Console\Command\Crypto\CryptoEncryptCommand;
use Rebet\Tests\RebetConsoleTestCase;
use Rebet\Tools\Utility\Nets;
use Rebet\Tools\Utility\Securities;
use Rebet\Tools\Utility\Strings;

class CryptoEncryptCommandTest extends RebetConsoleTestCase
{
    const AVIRABLE_COMMANDS = [CryptoEncryptCommand::class];

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
        $tester = $this->getCommandTester(CryptoEncryptCommand::NAME);
        $status = $tester->execute(array_merge(['plain' => $plain], $options));
        $this->assertSame(0, $status);
        $display = $tester->getDisplay();
        $this->assertStringStartsWith("Encrypted: ", $display);

        $encrypted = Strings::ltrim(trim($display), "Encrypted: ");
        $decrypted = Securities::decrypt(
            Nets::decodeBase64Url($encrypted),
            $options['--secret-key'] ?? null,
            null,
            $options['--hmac-secret-key'] ?? null,
            $options['--hmac-algorithm'] ?? null
        );
        $this->assertSame($plain, $decrypted);
    }
}
