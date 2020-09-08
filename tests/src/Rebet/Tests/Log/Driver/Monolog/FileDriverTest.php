<?php
namespace Rebet\Tests\Log\Driver\Monolog;

use Monolog\Handler\RotatingFileHandler;
use Rebet\DateTime\DateTime;
use Rebet\Log\Driver\Monolog\FileDriver;
use Rebet\Log\Driver\Monolog\Handler\SimpleBrowserConsoleHandler;
use Rebet\Log\LogLevel;
use Rebet\Tests\RebetTestCase;

class FileDriverTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        $this->vfs([
            'logs' => []
        ]);
    }

    public function test___construct()
    {
        $today = DateTime::today();

        $driver = new FileDriver('web', LogLevel::DEBUG, 'vfs://root/logs/unittest.log');
        $this->assertInstanceOf(FileDriver::class, $driver);

        $handlers = $driver->getHandlers();
        $this->assertSame(1, count($handlers));
        $this->assertInstanceOf(RotatingFileHandler::class, $handlers[0] ?? null);


        $driver = new FileDriver('web', LogLevel::DEBUG, 'vfs://root/logs/unittest.log', '{filename}_{date}', 'Ym', 12, 0664, false, true);
        $this->assertInstanceOf(FileDriver::class, $driver);

        $handlers = $driver->getHandlers();
        $this->assertSame(2, count($handlers));
        $this->assertInstanceOf(RotatingFileHandler::class, $handlers[0] ?? null);
        $this->assertInstanceOf(SimpleBrowserConsoleHandler::class, $handlers[1] ?? null);

        $driver->debug('TEST');

        $process_id = getmypid();
        $this->assertStringContainsString(" web/{$process_id} [DEBUG] TEST", file_get_contents('vfs://root/logs/unittest_'.$today->format('Ym').'.log'));
    }
}
