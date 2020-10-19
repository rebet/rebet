<?php
namespace Rebet\Tests\Mail\Plugins;

use Rebet\Mail\Mime\MimeMessage;
use Rebet\Mail\Plugins\AlwaysBccPlugin;
use Rebet\Mail\Transport\ArrayTransport;
use Rebet\Tests\RebetTestCase;
use Swift_Events_SendEvent;

class AlwaysBccPluginTest extends RebetTestCase
{
    public function test___constructAndBccs()
    {
        $plugin = new AlwaysBccPlugin('1@foo.com');
        $this->assertInstanceOf(AlwaysBccPlugin::class, $plugin);
        $this->assertSame(['1@foo.com' => null], $plugin->bccs());

        $plugin = new AlwaysBccPlugin('One <1@foo.com>');
        $this->assertInstanceOf(AlwaysBccPlugin::class, $plugin);
        $this->assertSame(['1@foo.com' => 'One'], $plugin->bccs());

        $plugin = new AlwaysBccPlugin(['1@foo.com' => 'One']);
        $this->assertInstanceOf(AlwaysBccPlugin::class, $plugin);
        $this->assertSame(['1@foo.com' => 'One'], $plugin->bccs());

        $plugin = new AlwaysBccPlugin(['1@foo.com' => 'One', 'Two <2@foo.com>', '3@foo.com']);
        $this->assertInstanceOf(AlwaysBccPlugin::class, $plugin);
        $this->assertSame(['1@foo.com' => 'One', '2@foo.com' => 'Two', '3@foo.com' => null], $plugin->bccs());
    }

    public function test_beforeSendPerformed()
    {
        $plugin  = new AlwaysBccPlugin(['1@foo.com' => 'One', 'Two <2@foo.com>', '3@foo.com']);
        $message = new MimeMessage();
        $event   = new Swift_Events_SendEvent(new ArrayTransport(), $message);
        $this->assertSame(null, $message->getBcc());
        $plugin->beforeSendPerformed($event);
        $this->assertSame(['1@foo.com' => 'One', '2@foo.com' => 'Two', '3@foo.com' => null], $message->getBcc());
    }
}
