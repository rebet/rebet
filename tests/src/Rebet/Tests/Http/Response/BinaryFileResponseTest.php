<?php
namespace Rebet\Tests\Http\Response;

use Rebet\Application\App;
use Rebet\Http\Response;
use Rebet\Http\Response\BinaryFileResponse;
use Rebet\Tests\RebetTestCase;

class BinaryFileResponseTest extends RebetTestCase
{
    public function test___construct()
    {
        $response = new BinaryFileResponse(App::structure()->public('/assets/img/72x72.png'));
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertInstanceOf(Response::class, $response);
    }
}
