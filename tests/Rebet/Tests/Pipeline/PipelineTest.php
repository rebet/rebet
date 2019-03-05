<?php
namespace Rebet\Tests\Pipeline;

use Rebet\Pipeline\Pipeline;
use Rebet\Tests\RebetTestCase;

class PipelineTest extends RebetTestCase
{
    private $pipeline;

    public function setup()
    {
        parent::setUp();
        $this->pipeline = new Pipeline();
    }

    /**
     * @expectedException Rebet\Common\Exception\LogicException
     * @expectedExceptionMessage Pipeline not build yet. You shold buld a pipeline using then() first.
     */
    public function test_send_beforePipelineBuild()
    {
        $output = $this->pipeline->send('onion');
    }

    public function test_usage_basic()
    {
        $this->pipeline->through([
            PipelineTest_Wrapper::class,
            function ($input, $next) {
                return $next($input.'!');
            }
        ])->then(function ($input) {
            return $input;
        });

        $output = $this->pipeline->send('onion');
        $this->assertSame('(onion!)', $output);

        $output = $this->pipeline->via('before')->send('onion');
        $this->assertSame('(onion)!', $output);

        $output = $this->pipeline->via('both')->send('onion');
        $this->assertSame('((onion)!)', $output);
    }

    public function test_usage_objectAndArray()
    {
        $this->pipeline->through(
            function ($input, $next) {
                return strtoupper($next($input));
            },
            new PipelineTest_Wrapper(),
            [PipelineTest_Wrapper::class, '[', ']'],
            function ($input, $next) {
                return $next($input.'!');
            }
        )->then(function ($input) {
            return $input;
        });

        $output = $this->pipeline->send('onion');
        $this->assertSame('([ONION!])', $output);
    }

    public function test_getDestination()
    {
        $this->assertNull($this->pipeline->getDestination());
        $destination = function ($input) {
            return $input;
        };
        $this->pipeline->then($destination);
        $this->assertSame($destination, $this->pipeline->getDestination());
    }

    public function test_invoke()
    {
        $this->pipeline->through(
            new PipelineTest_Wrapper(),
            [PipelineTest_Wrapper::class, '[', ']'],
            function ($input, $next) {
                return $next($input.'!');
            }
        )->then(function ($input) {
            return $input;
        });

        $this->assertSameOutbuffer(
            '[terminate](terminate)',
            function () {
                $this->pipeline->invoke('terminate');
            }
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
        return $this->open.$next($input).$this->close;
    }

    public function before($input, $next)
    {
        return $next($this->open.$input.$this->close);
    }

    public function both($input, $next)
    {
        $output = $next($this->open.$input.$this->close);
        return $this->open.$output.$this->close;
    }

    public function terminate()
    {
        echo $this->open.'terminate'.$this->close;
    }

    public function set($open, $close)
    {
        $this->open  = $open;
        $this->close = $close;
    }
}
