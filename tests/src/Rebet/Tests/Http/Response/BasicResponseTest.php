<?php
namespace Rebet\Tests\Http\Response;

use Rebet\Http\Response;
use Rebet\Http\Response\BasicResponse;
use Rebet\Tests\RebetTestCase;

class BasicResponseTest extends RebetTestCase
{
    public function test___construct()
    {
        $response = new BasicResponse();
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertInstanceOf(Response::class, $response);
    }
}
