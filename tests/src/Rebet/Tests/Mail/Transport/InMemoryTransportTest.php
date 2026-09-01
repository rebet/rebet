<?php
namespace Rebet\Tests\Mail\Transport;

use Rebet\Mail\Transport\InMemoryTransport;
use Rebet\Tests\RebetTestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;

class InMemoryTransportTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(TransportInterface::class, new InMemoryTransport());
    }

    public function test_getSentMessage()
    {
        $transport = new InMemoryTransport();
        $this->assertNull($transport->getSentMessage());

        $envelope = new Envelope(new Address('from@test.local'), [new Address('to@test.local')]);
        $sent     = $transport->send(new RawMessage('message 1'), $envelope);

        $this->assertInstanceOf(SentMessage::class, $transport->getSentMessage());
        $this->assertSame($sent, $transport->getSentMessage());
        $this->assertSame('message 1', $transport->getSentMessage()->getOriginalMessage()->toString());

        // Only the latest sent message is kept.
        $transport->send(new RawMessage('message 2'), $envelope);
        $this->assertSame('message 2', $transport->getSentMessage()->getOriginalMessage()->toString());
    }

    public function test___toString()
    {
        $this->assertSame('rebet://in-memory', (string) (new InMemoryTransport()));
    }
}
