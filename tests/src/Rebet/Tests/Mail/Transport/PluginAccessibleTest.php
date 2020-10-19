<?php
namespace Rebet\Tests\Mail\Transport;

use Rebet\Mail\Transport\SendmailTransport;
use Rebet\Tests\RebetTestCase;
use Swift_Plugins_AntiFloodPlugin;
use Swift_Plugins_BandwidthMonitorPlugin;

class PluginAccessibleTest extends RebetTestCase
{
    public function test_registerPluginAndPlugins()
    {
        $transport = new SendmailTransport();
        $this->assertSame([], $transport->plugins());

        $p1 = new Swift_Plugins_AntiFloodPlugin();
        $transport->registerPlugin($p1);
        $this->assertSame([$p1], $transport->plugins());

        $p2 = new Swift_Plugins_AntiFloodPlugin(10, 3);
        $transport->registerPlugin($p2);
        $this->assertSame([$p1, $p2], $transport->plugins());

        $p3 = new Swift_Plugins_BandwidthMonitorPlugin();
        $transport->registerPlugin($p3);
        $this->assertSame([$p1, $p2, $p3], $transport->plugins());

        $transport->registerPlugin($p2);
        $this->assertSame([$p1, $p2, $p3], $transport->plugins());
    }
}
