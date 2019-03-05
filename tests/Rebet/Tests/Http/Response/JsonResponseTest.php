<?php
namespace Rebet\Tests\Http\Response;

use Rebet\Http\Response;
use Rebet\Http\Response\JsonResponse;
use Rebet\Tests\RebetTestCase;

class JsonResponseTest extends RebetTestCase
{
    public function test___construct()
    {
        $response = new JsonResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertInstanceOf(Response::class, $response);
    }
}
