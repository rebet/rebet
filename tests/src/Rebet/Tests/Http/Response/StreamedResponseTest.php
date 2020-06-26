<?php
namespace Rebet\Tests\Http\Response;

use Rebet\Http\Response;
use Rebet\Http\Response\StreamedResponse;
use Rebet\Tests\RebetTestCase;

class StreamedResponseTest extends RebetTestCase
{
    public function test___construct()
    {
        $response = new StreamedResponse();
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertInstanceOf(Response::class, $response);
    }
}
