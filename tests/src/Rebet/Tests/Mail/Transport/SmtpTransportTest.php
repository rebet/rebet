<?php
namespace Rebet\Tests\Mail\Transport;

use Rebet\Mail\Plugins\AlwaysBccPlugin;
use Rebet\Mail\Plugins\LoggingPlugin;
use Rebet\Mail\Transport\SmtpTransport;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Exception\LogicException;
use Swift_Plugins_AntiFloodPlugin;
use Swift_Plugins_BandwidthMonitorPlugin;
use Swift_Plugins_ImpersonatePlugin;
use Swift_Plugins_PopBeforeSmtpPlugin;
use Swift_Plugins_RedirectingPlugin;
use Swift_Plugins_ThrottlerPlugin;

class SmtpTransportTest extends RebetTestCase
{
    public function test___construct()
    {
        $transport = new SmtpTransport('127.0.0.1', 587, 'username', 'password', [
            'timeout'           => 30,
            'source_ip'         => '0.0.0.0',
            'local_domain'      => 'domain.local',
            'stream_options'    => ['ssl' => ['peer_name' => 'bar.com']],
            'disable_ca_check'  => true,
            'antiflood'         => ['threshold' => 9, 'sleep' => 1],
            'redirecting'       => ['recipient' => 'foo@bar.com', 'whitelist' => ['foo@bar.com', 'baz@bar.com']],
            'sender'            => 'sender@bar.com',
            'bandwidth_monitor' => true,
            'throttle'          => ['rate' => 3, 'mode' => Swift_Plugins_ThrottlerPlugin::MESSAGES_PER_MINUTE],
            'pop_before_smtp'   => ['port' => 995],
            'logging'           => true,
            'always_bcc'        => 'always_bcc@bar.com',
        ]);
        $this->assertSame('127.0.0.1', $transport->getHost());
        $this->assertSame(587, $transport->getPort());
        $this->assertSame('username', $transport->getUsername());
        $this->assertSame('password', $transport->getPassword());
        $this->assertSame('tls', $transport->getEncryption());
        $this->assertSame(30, $transport->getTimeout());
        $this->assertSame('0.0.0.0', $transport->getSourceIp());
        $this->assertSame('domain.local', $transport->getLocalDomain());
        $this->assertSame(['ssl' => ['peer_name' => 'bar.com', 'allow_self_signed' => true, 'verify_peer' => false, 'verify_peer_name' => false]], $transport->getStreamOptions());
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
        $this->assertInstanceOf(Swift_Plugins_PopBeforeSmtpPlugin::class, $plugin = $transport->plugins()[5] ?? null);
        $this->assertSame('127.0.0.1', $this->inspect($plugin, 'host'));
        $this->assertSame(995, $this->inspect($plugin, 'port'));
        $this->assertSame('tls', $this->inspect($plugin, 'crypto'));
        $this->assertSame('username', $this->inspect($plugin, 'username'));
        $this->assertSame('password', $this->inspect($plugin, 'password'));
        $this->assertSame($transport, $this->inspect($plugin, 'transport'));
        $this->assertInstanceOf(LoggingPlugin::class, $plugin = $transport->plugins()[6] ?? null);
        $this->assertInstanceOf(AlwaysBccPlugin::class, $plugin = $transport->plugins()[7] ?? null);
        $this->assertSame(['always_bcc@bar.com' => null], $plugin->bccs());
    }

    public function test___construct_invalidOption()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Invalid option 'invalid_option' was given.");
        $transport = new SmtpTransport('127.0.0.1', 587, 'username', 'password', [
            'invalid_option' => 'invalid',
        ]);
    }
}
