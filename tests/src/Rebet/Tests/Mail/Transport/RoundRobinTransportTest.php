<?php
namespace Rebet\Tests\Mail\Transport;

use Psr\Log\NullLogger;
use Rebet\Mail\Transport\InMemoryTransport;
use Rebet\Mail\Transport\RoundRobinTransport;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Reflection\Reflector;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;

class RoundRobinTransportTest extends RebetTestCase
{
    public function test___construct()
    {
        $transport = new RoundRobinTransport([
            InMemoryTransport::class,
            new InMemoryTransport(),
            ['@factory' => InMemoryTransport::class],
        ], 30);

        $transports = $this->inspect($transport, 'transports');
        $this->assertCount(3, $transports);
        foreach ($transports as $t) {
            $this->assertInstanceOf(InMemoryTransport::class, $t);
        }
        $this->assertSame(30, $this->inspect($transport, 'retryPeriod'));
        $this->assertInstanceOf(NullLogger::class, $this->inspect($transport, 'logger'));
    }

    public function test___construct_defaults()
    {
        $transport = new RoundRobinTransport([new NullTransport()]);
        $this->assertSame(60, $this->inspect($transport, 'retryPeriod'));
        $this->assertInstanceOf(NullLogger::class, $this->inspect($transport, 'logger'));
    }

    public function test_send_rotatesAcrossTransports()
    {
        $t1 = new InMemoryTransport();
        $t2 = new InMemoryTransport();
        $t3 = new InMemoryTransport();

        $transport = new RoundRobinTransport([$t1, $t2, $t3]);
        // The initial cursor is randomized by the parent class, so fix it for a deterministic test.
        Reflector::set($transport, 'cursor', 0, true);

        $envelope = new Envelope(new Address('from@test.local'), [new Address('to@test.local')]);
        $transport->send(new RawMessage('message 1'), $envelope);
        $transport->send(new RawMessage('message 2'), $envelope);
        $transport->send(new RawMessage('message 3'), $envelope);

        $this->assertSame('message 1', $t1->getSentMessage()->getOriginalMessage()->toString());
        $this->assertSame('message 2', $t2->getSentMessage()->getOriginalMessage()->toString());
        $this->assertSame('message 3', $t3->getSentMessage()->getOriginalMessage()->toString());

        // The 4th send should wrap back around to $t1.
        $transport->send(new RawMessage('message 4'), $envelope);
        $this->assertSame('message 4', $t1->getSentMessage()->getOriginalMessage()->toString());
    }
}
