<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Log\Formatter\DefaultFormatter;
use Rebet\DateTime\DateTime;
use Rebet\Config\Config;
use Rebet\Config\App;

class DefaultFormatterTest extends RebetTestCase {

    private $now;
    private $formatter;

    public function setUp() {
        Config::clear();
        App::setTimezone('UTC');
        DateTime::setTestNow('2010-10-20 10:20:30.040050');

        $this->now       = DateTime::now();
        $this->formatter = DefaultFormatter::create();
    }

    public function test_create() {
        $this->assertInstanceOf(DefaultFormatter::class, DefaultFormatter::create());
    }

    public function test_format() {
        // $this->formatter->format($this->now, );
    }
}