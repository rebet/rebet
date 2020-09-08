<?php
namespace Rebet\Tests\Application;

use Rebet\Auth\AuthUser;
use Rebet\Auth\Event\Authentication;
use Rebet\Auth\Event\Signined;
use Rebet\Auth\Event\SigninFailed;
use Rebet\Auth\Event\Signouted;
use Rebet\Config\Config;
use Rebet\Event\Event;
use Rebet\Http\Request;
use Rebet\Tests\RebetTestCase;

class EventTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        Config::application([
            Event::class => [
                'listeners' => [
                    EventTest_EchoSignined::class,
                    EventTest_EchoSignouted::class,
                    EventTest_EchoAuthentication::class,
                    function (Signined $event) { echo '4'; },
                    function ($event) { echo '5'; },
                    function (string $event) { echo "6-{$event}"; },
                    [EventTest_EchoSigninFailed::class, 'A'],
                    [EventTest_EchoSigninFailed::class, 'B'],
                ],
            ],
        ]);
    }

    public function test_listenAndClear()
    {
        $this->assertSameStdout('6-a', function () { Event::dispatch('a'); });
        $this->assertSameStdout('', function () { Event::dispatch(1); });

        Event::listen(function (int $event) { echo $event; });
        $this->assertSameStdout('6-a', function () { Event::dispatch('a'); });
        $this->assertSameStdout('1', function () { Event::dispatch(1); });

        Event::clear();
        $this->assertSameStdout('', function () { Event::dispatch('a'); });
        $this->assertSameStdout('', function () { Event::dispatch(1); });
    }

    public function test_dispatch()
    {
        $this->assertSameStdout(
            '143',
            function () {
                Event::dispatch(new Signined(Request::create('/'), AuthUser::guest(), false));
            }
        );
        $this->assertSameStdout(
            '23',
            function () {
                Event::dispatch(new Signouted(Request::create('/'), AuthUser::guest()));
            }
        );
        $this->assertSameStdout(
            '6-test',
            function () {
                Event::dispatch('test');
            }
        );
        $this->assertSameStdout(
            '3AB',
            function () {
                Event::dispatch(new SigninFailed(Request::create('/')));
            }
        );
    }
}

class EventTest_EchoSignined
{
    public function handle(Signined $event)
    {
        echo '1';
    }
}

class EventTest_EchoSignouted
{
    public function handle(Signouted $event)
    {
        echo '2';
    }
}

class EventTest_EchoAuthentication
{
    public function handle(Authentication $event)
    {
        echo '3';
    }
}

class EventTest_EchoSigninFailed
{
    private $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function handle(SigninFailed $event)
    {
        echo $this->text;
    }
}
