<?php
namespace Rebet\Tests\Mail\Plugins;

use Rebet\Log\Log;
use Rebet\Mail\Mime\MimeMessage;
use Rebet\Mail\Plugins\LoggingPlugin;
use Rebet\Mail\Transport\ArrayTransport;
use Rebet\Tests\RebetTestCase;
use Swift_Events_SendEvent;

class LoggingPluginTest extends RebetTestCase
{
    public function test___constructAndBccs()
    {
        $this->assertInstanceOf(LoggingPlugin::class, new LoggingPlugin());
    }

    public function test_sendPerformed()
    {
        $plugin  = new LoggingPlugin();
        $message = new MimeMessage();
        $message->setSubject('件名');
        $message->setTo('1@foo.com', '宛先');
        $message->setBody('本文');
        $event   = new Swift_Events_SendEvent(new ArrayTransport(), $message);
        $event->setResult(Swift_Events_SendEvent::RESULT_SUCCESS);
        $plugin->sendPerformed($event);
        $this->assertContainsString(
            [
                'Subject: 件名',
                'To: 宛先 <1@foo.com>',
                '本文',
            ],
            Log::channel()->driver()->formatted()
        );
    }
}
