<?php
namespace Rebet\Tests\Mail\Transport;

use Psr\Log\NullLogger;
use Rebet\Mail\Transport\FailoverTransport;
use Rebet\Mail\Transport\InMemoryTransport;
use Rebet\Tests\RebetTestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;

class FailoverTransportTest extends RebetTestCase
{
    public function test___construct()
    {
        $transport = new FailoverTransport([
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
        $transport = new FailoverTransport([new NullTransport()]);
        $this->assertSame(60, $this->inspect($transport, 'retryPeriod'));
        $this->assertInstanceOf(NullLogger::class, $this->inspect($transport, 'logger'));
    }

    public function test_send_failsOverToNextTransport()
    {
        $failing = $this->createFailingTransport();
        $t2      = new InMemoryTransport();

        $transport = new FailoverTransport([$failing, $t2]);
        $envelope  = new Envelope(new Address('from@test.local'), [new Address('to@test.local')]);

        $transport->send(new RawMessage('message 1'), $envelope);
        $this->assertSame('message 1', $t2->getSentMessage()->getOriginalMessage()->toString());

        // Once failed over, $t2 becomes the "current" transport, so it keeps being used directly.
        $transport->send(new RawMessage('message 2'), $envelope);
        $this->assertSame('message 2', $t2->getSentMessage()->getOriginalMessage()->toString());
    }

    public function test_send_throwsWhenAllTransportsFail()
    {
        $transport = new FailoverTransport([$this->createFailingTransport(), $this->createFailingTransport()]);
        $envelope  = new Envelope(new Address('from@test.local'), [new Address('to@test.local')]);

        $this->expectException(TransportException::class);
        $transport->send(new RawMessage('message'), $envelope);
    }

    private function createFailingTransport() : AbstractTransport
    {
        return new class extends AbstractTransport {
            protected function doSend(SentMessage $message) : void
            {
                throw new TransportException('Simulated transport failure.');
            }

            public function __toString() : string
            {
                return 'failing://test';
            }
        };
    }
}
