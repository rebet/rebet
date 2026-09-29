<?php

declare(strict_types=1);

namespace Rebet\Tests\Pipeline;

use Override;
use Rebet\Pipeline\Pipeline;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Exception\LogicException;

class PipelineTest extends RebetTestCase
{
    private $pipeline;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->pipeline = new Pipeline();
    }

    public function test_send_beforePipelineBuild(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Pipeline not build yet. You shold buld a pipeline using then() first.");

        $output = $this->pipeline->send('onion');
    }

    public function test_usage_basic(): void
    {
        $this->pipeline->through([
            PipelineTest_Wrapper::class,
            fn($input, $next) => $next($input . '!'),
        ])->then(fn($input) => $input);

        $output = $this->pipeline->send('onion');
        $this->assertSame('(onion!)', $output);

        $output = $this->pipeline->via('before')->send('onion');
        $this->assertSame('(onion)!', $output);

        $output = $this->pipeline->via('both')->send('onion');
        $this->assertSame('((onion)!)', $output);
    }

    public function test_usage_objectAndArray(): void
    {
        $this->pipeline->through(
            fn($input, $next) => strtoupper($next($input)),
            new PipelineTest_Wrapper(),
            [PipelineTest_Wrapper::class, '[', ']'],
            fn($input, $next) => $next($input . '!'),
        )->then(fn($input) => $input);

        $output = $this->pipeline->send('onion');
        $this->assertSame('([ONION!])', $output);
    }

    public function test_getDestination(): void
    {
        $this->assertNull($this->pipeline->getDestination());
        $destination = fn($input) => $input;
        $this->pipeline->then($destination);
        $this->assertSame($destination, $this->pipeline->getDestination());
    }

    public function test_invoke(): void
    {
        $this->pipeline->through(
            new PipelineTest_Wrapper(),
            [PipelineTest_Wrapper::class, '[', ']'],
            fn($input, $next) => $next($input . '!'),
        )->then(fn($input) => $input);

        $this->assertStdoutEquals(
            '[terminate](terminate)',
            function (): void {
                $this->pipeline->invoke('terminate');
            },
        );

        $output = $this->pipeline->send('onion');
        $this->assertSame('([onion!])', $output);

        $this->pipeline->invoke('set', '<', '>');
        $output = $this->pipeline->send('onion');
        $this->assertSame('<<onion!>>', $output);
    }
}

class PipelineTest_Wrapper
{
    private $open;
    private $close;

    public function __construct($open = '(', $close = ')')
    {
        $this->open  = $open;
        $this->close = $close;
    }

    public function handle($input, $next)
    {
        return $this->after($input, $next);
    }

    public function after($input, $next)
    {
        return $this->open . $next($input) . $this->close;
    }

    public function before($input, $next)
    {
        return $next($this->open . $input . $this->close);
    }

    public function both($input, $next)
    {
        $output = $next($this->open . $input . $this->close);
        return $this->open . $output . $this->close;
    }

    public function terminate(): void
    {
        echo $this->open . 'terminate' . $this->close;
    }

    public function set($open, $close): void
    {
        $this->open  = $open;
        $this->close = $close;
    }
}
