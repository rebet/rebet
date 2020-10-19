<?php
namespace Rebet\Tests\Mail\Transport;

use Rebet\Mail\Plugins\AlwaysBccPlugin;
use Rebet\Mail\Plugins\LoggingPlugin;
use Rebet\Mail\Transport\SendmailTransport;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Exception\LogicException;
use Swift_Plugins_AntiFloodPlugin;
use Swift_Plugins_BandwidthMonitorPlugin;
use Swift_Plugins_ImpersonatePlugin;
use Swift_Plugins_RedirectingPlugin;
use Swift_Plugins_ThrottlerPlugin;

class SendmailTransportTest extends RebetTestCase
{
    public function test___construct()
    {
        $transport = new SendmailTransport('/usr/sbin/sendmail -bs', [
            'source_ip'         => '0.0.0.0',
            'local_domain'      => 'domain.local',
            'antiflood'         => ['threshold' => 9, 'sleep' => 1],
            'redirecting'       => ['recipient' => 'foo@bar.com', 'whitelist' => ['foo@bar.com', 'baz@bar.com']],
            'sender'            => 'sender@bar.com',
            'bandwidth_monitor' => true,
            'throttle'          => ['rate' => 3, 'mode' => Swift_Plugins_ThrottlerPlugin::MESSAGES_PER_MINUTE],
            'logging'           => true,
            'always_bcc'        => 'always_bcc@bar.com',
        ]);
        $this->assertSame('/usr/sbin/sendmail -bs', $transport->getCommand());
        $this->assertSame('0.0.0.0', $transport->getSourceIp());
        $this->assertSame('domain.local', $transport->getLocalDomain());
        $this->assertInstanceOf(Swift_Plugins_AntiFloodPlugin::class, $plugin = $transport->plugins()[0] ?? null);
        $this->assertSame(9, $plugin->getThreshold());
        $this->assertSame(1, $plugin->getSleepTime());
        $this->assertInstanceOf(Swift_Plugins_RedirectingPlugin::class, $plugin = $transport->plugins()[1] ?? null);
        $this->assertSame('foo@bar.com', $plugin->getRecipient());
        $this->assertSame(['foo@bar.com', 'baz@bar.com'], $plugin->getWhitelist());
        $this->assertInstanceOf(Swift_Plugins_ImpersonatePlugin::class, $plugin = $transport->plugins()[2] ?? null);
        $this->assertSame('sender@bar.com', $this->inspect($plugin, 'sender'));
        $this->assertInstanceOf(Swift_Plugins_BandwidthMonitorPlugin::class, $plugin = $transport->plugins()[3] ?? null);
        $this->assertInstanceOf(Swift_Plugins_ThrottlerPlugin::class, $plugin = $transport->plugins()[4] ?? null);
        $this->assertSame(3, $this->inspect($plugin, 'rate'));
        $this->assertSame(Swift_Plugins_ThrottlerPlugin::MESSAGES_PER_MINUTE, $this->inspect($plugin, 'mode'));
        $this->assertInstanceOf(LoggingPlugin::class, $plugin = $transport->plugins()[5] ?? null);
        $this->assertInstanceOf(AlwaysBccPlugin::class, $plugin = $transport->plugins()[6] ?? null);
        $this->assertSame(['always_bcc@bar.com' => null], $plugin->bccs());
    }

    public function test___construct_invalidOption()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Invalid option 'invalid_option' was given.");
        $transport = new SendmailTransport('/usr/sbin/sendmail -bs', [
            'invalid_option' => 'invalid',
        ]);
    }
}
